<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Comercial\StoreAtributoRequest;
use App\Http\Requests\Operacion\Comercial\StoreCatalogoBaseRequest;
use App\Http\Requests\Operacion\Comercial\StoreProductoRequest;
use App\Http\Requests\Operacion\Comercial\StoreValorAtributoRequest;
use App\Http\Requests\Operacion\Comercial\UpdateAtributoRequest;
use App\Http\Requests\Operacion\Comercial\UpdateCatalogoBaseRequest;
use App\Http\Requests\Operacion\Comercial\UpdateProductoRequest;
use App\Http\Requests\Operacion\Comercial\UpdateProductoSkuRequest;
use App\Http\Requests\Operacion\Comercial\UpdateValorAtributoRequest;
use App\Services\Operacion\Comercial\AtributoService;
use App\Services\Operacion\Comercial\CatalogoBaseService;
use App\Services\Operacion\Comercial\ModeloService;
use App\Services\Operacion\Comercial\ProductoImagenTemporalService;
use App\Services\Operacion\Comercial\ProductoSkuService;
use App\Services\Operacion\Comercial\ProductoService;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperacionCatalogoComercialController extends Controller
{
    public function __construct(
        private readonly AtributoService $atributoService,
        private readonly CatalogoBaseService $catalogoBaseService,
        private readonly ModeloService $modeloService,
        private readonly ProductoImagenTemporalService $productoImagenTemporalService,
        private readonly ProductoService $productoService,
        private readonly ProductoSkuService $productoSkuService,
    ) {
    }

    public function index()
    {
        return redirect()->route('desktop.operacion.catalogo_comercial.base.index');
    }

    public function base()
    {
        return view('desktop.operacion.catalogo_comercial.base', [
            'submenus' => $this->submenus(),
            'catalogTypes' => $this->catalogTypes(),
            'opciones' => [
                'marcas' => $this->catalogoBaseService->opcionesActivas('marcas'),
                'lineas' => $this->catalogoBaseService->opcionesActivas('lineas'),
            ],
            'permisosUI' => $this->permisosUI(),
        ]);
    }

    public function atributos()
    {
        return view('desktop.operacion.catalogo_comercial.atributos', [
            'submenus' => $this->submenus(),
            'opciones' => [
                'atributos' => $this->atributoService->opcionesAtributosActivos(),
            ],
            'permisosUI' => $this->permisosUI(),
        ]);
    }

    public function productos()
    {
        return view('desktop.operacion.catalogo_comercial.productos', $this->productoFormViewData());
    }

    public function crearProducto()
    {
        return view('desktop.operacion.catalogo_comercial.producto_form', array_merge(
            $this->productoFormViewData(),
            [
                'pageTitle' => 'Nuevo producto',
                'submitLabel' => 'Guardar producto',
            ],
        ));
    }

    public function editarProducto(int $producto)
    {
        $item = $this->productoService->obtenerPorId($producto);

        return view('desktop.operacion.catalogo_comercial.producto_form', array_merge(
            $this->productoFormViewData($item),
            [
                'pageTitle' => 'Editar producto',
                'submitLabel' => 'Guardar cambios',
            ],
        ));
    }

    public function iniciarCargaTemporalProducto(Request $request): JsonResponse
    {
        $baseUrl = $request->string('public_base_url')->toString();
        $baseUrl = $baseUrl !== '' ? $baseUrl : $request->getSchemeAndHttpHost();

        return response()->json([
            'data' => $this->productoImagenTemporalService->crearSesionCarga($baseUrl),
        ]);
    }

    public function estadoCargaTemporalProducto(string $token): JsonResponse
    {
        return response()->json([
            'data' => $this->productoImagenTemporalService->obtenerEstado($token),
        ]);
    }

    public function vistaCargaMovilProducto(string $token)
    {
        return view('operacion.catalogo_comercial.producto_imagen_movil', [
            'token' => $token,
        ]);
    }

    public function subirImagenMovilProducto(Request $request, string $token)
    {
        $request->validate([
            'imagen' => ['required', 'image', 'max:5120'],
        ], [
            'imagen.required' => 'Selecciona una imagen para continuar.',
            'imagen.image' => 'El archivo enviado no es una imagen válida.',
            'imagen.max' => 'La imagen no debe superar los 5 MB.',
        ]);

        $this->productoImagenTemporalService->guardarDesdeMovil($token, $request->file('imagen'));

        return back()->with('status', 'Imagen cargada correctamente. Ya puedes volver a la computadora.');
    }

    public function skus()
    {
        return view('desktop.operacion.catalogo_comercial.skus', $this->skuViewData());
    }

    public function proveedores()
    {
        return $this->renderPlaceholder('proveedores', 'Proveedores', 'La experiencia Desktop de proveedores se integrará aquí en la siguiente etapa.');
    }

    public function etiquetado()
    {
        return $this->renderPlaceholder('etiquetado', 'Etiquetado', 'La experiencia Desktop de etiquetado se integrará aquí en la siguiente etapa.');
    }

    public function dataCatalogoBase(Request $request, string $tipo): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);

        $registros = $this->catalogoBaseService->listar($tipo, [
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $registros->map(function ($item) use ($tipo): array {
            return match ($tipo) {
                'marcas' => [
                    'id' => $item->mrc_id,
                    'nombre' => $item->mrc_nombre,
                    'clave' => $item->mrc_clave,
                    'estatus' => $item->mrc_estatus,
                ],
                'lineas' => [
                    'id' => $item->lna_id,
                    'nombre' => $item->lna_nombre,
                    'clave' => $item->lna_clave,
                    'estatus' => $item->lna_estatus,
                ],
                'categorias' => [
                    'id' => $item->ctg_id,
                    'nombre' => $item->ctg_nombre,
                    'clave' => $item->ctg_clave,
                    'estatus' => $item->ctg_estatus,
                    'lna_id' => $item->ctg_lna_id,
                    'linea' => $item->linea?->lna_nombre,
                ],
                'unidades' => [
                    'id' => $item->umd_id,
                    'nombre' => $item->umd_nombre,
                    'codigo' => $item->umd_codigo,
                    'clave' => $item->umd_clave,
                    'estatus' => $item->umd_estatus,
                    'tipo_cantidad' => $item->umd_tipo_cantidad,
                    'es_predeterminada' => (bool) $item->umd_es_predeterminada,
                ],
                'conceptos' => [
                    'id' => $item->cpt_id,
                    'nombre' => $item->cpt_nombre,
                    'clave' => $item->cpt_clave,
                    'estatus' => $item->cpt_estatus,
                ],
                'motivos' => [
                    'id' => $item->mtv_id,
                    'nombre' => $item->mtv_nombre,
                    'clave' => $item->mtv_clave,
                    'estatus' => $item->mtv_estatus,
                ],
                default => [],
            };
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showCatalogoBase(string $tipo, int $id): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);
        $item = $this->catalogoBaseService->obtenerPorId($tipo, $id);

        $data = match ($tipo) {
            'marcas' => ['id' => $item->mrc_id, 'nombre' => $item->mrc_nombre, 'clave' => $item->mrc_clave, 'estatus' => $item->mrc_estatus],
            'lineas' => ['id' => $item->lna_id, 'nombre' => $item->lna_nombre, 'clave' => $item->lna_clave, 'estatus' => $item->lna_estatus],
            'categorias' => ['id' => $item->ctg_id, 'nombre' => $item->ctg_nombre, 'clave' => $item->ctg_clave, 'estatus' => $item->ctg_estatus, 'lna_id' => $item->ctg_lna_id, 'linea' => $item->linea?->lna_nombre],
            'unidades' => ['id' => $item->umd_id, 'nombre' => $item->umd_nombre, 'codigo' => $item->umd_codigo, 'clave' => $item->umd_clave, 'estatus' => $item->umd_estatus, 'tipo_cantidad' => $item->umd_tipo_cantidad, 'es_predeterminada' => (bool) $item->umd_es_predeterminada],
            'conceptos' => ['id' => $item->cpt_id, 'nombre' => $item->cpt_nombre, 'clave' => $item->cpt_clave, 'estatus' => $item->cpt_estatus],
            'motivos' => ['id' => $item->mtv_id, 'nombre' => $item->mtv_nombre, 'clave' => $item->mtv_clave, 'estatus' => $item->mtv_estatus],
        };

        return response()->json(['data' => $data]);
    }

    public function storeCatalogoBase(StoreCatalogoBaseRequest $request, string $tipo): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);
        $registro = $this->catalogoBaseService->crear($tipo, $request, $request->validated());

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'data' => ['id' => $registro->{$this->idColumnCatalogo($tipo)}],
        ]);
    }

    public function updateCatalogoBase(UpdateCatalogoBaseRequest $request, string $tipo, int $id): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);
        $this->catalogoBaseService->actualizar($tipo, $request, $id, $request->validated());

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    public function cambiarEstatusCatalogoBase(Request $request, string $tipo, int $id): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);

        $request->validate([
            'estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'estatus.required' => 'El estatus es obligatorio.',
            'estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->catalogoBaseService->cambiarEstatus($tipo, $request, $id, $request->string('estatus')->toString());

        return response()->json([
            'message' => 'Estatus actualizado correctamente.',
            'data' => ['estatus' => $registro->{$this->estatusColumnCatalogo($tipo)}],
        ]);
    }

    public function eliminarCatalogoBase(Request $request, string $tipo, int $id): JsonResponse
    {
        $this->validarTipoCatalogo($tipo);
        $this->catalogoBaseService->eliminar($tipo, $request, $id);

        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }

    public function dataModelos(Request $request): JsonResponse
    {
        $modelos = $this->modeloService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $modelos->map(fn ($item) => [
            'mdl_id' => $item->mdl_id,
            'mdl_nombre' => $item->mdl_nombre,
            'mdl_clave' => $item->mdl_clave,
            'mdl_estatus' => $item->mdl_estatus,
            'marcas' => $item->marcas->map(fn ($marca) => [
                'id' => $marca->mrc_id,
                'nombre' => $marca->mrc_nombre,
            ])->values(),
            'marcas_texto' => $item->marcas->pluck('mrc_nombre')->join(', ') ?: '-',
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function showModelo(int $modelo): JsonResponse
    {
        $item = $this->modeloService->obtenerPorId($modelo);

        return response()->json(['data' => [
            'mdl_id' => $item->mdl_id,
            'mdl_nombre' => $item->mdl_nombre,
            'mdl_clave' => $item->mdl_clave,
            'mdl_estatus' => $item->mdl_estatus,
            'marca_ids' => $item->marcas->pluck('mrc_id')->values(),
            'marcas_texto' => $item->marcas->pluck('mrc_nombre')->join(', ') ?: '-',
        ]]);
    }

    public function storeModelo(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'clave' => ['nullable', 'string', 'max:40'],
            'estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'marca_ids' => ['nullable', 'array'],
            'marca_ids.*' => ['integer', 'exists:tbl_marcas_mrc,mrc_id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'estatus.required' => 'El estatus es obligatorio.',
        ]);

        $item = $this->modeloService->crear($request, $datos);

        return response()->json([
            'message' => 'Modelo creado correctamente.',
            'data' => ['mdl_id' => $item->mdl_id],
        ]);
    }

    public function updateModelo(Request $request, int $modelo): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'clave' => ['nullable', 'string', 'max:40'],
            'estatus' => ['required', Rule::in(['activo', 'inactivo'])],
            'marca_ids' => ['nullable', 'array'],
            'marca_ids.*' => ['integer', 'exists:tbl_marcas_mrc,mrc_id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'estatus.required' => 'El estatus es obligatorio.',
        ]);

        $this->modeloService->actualizar($request, $modelo, $datos);

        return response()->json(['message' => 'Modelo actualizado correctamente.']);
    }

    public function cambiarEstatusModelo(Request $request, int $modelo): JsonResponse
    {
        $request->validate([
            'mdl_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);

        $registro = $this->modeloService->cambiarEstatus($request, $modelo, $request->string('mdl_estatus')->toString());

        return response()->json([
            'message' => 'Estatus del modelo actualizado correctamente.',
            'data' => ['mdl_estatus' => $registro->mdl_estatus],
        ]);
    }

    public function eliminarModelo(Request $request, int $modelo): JsonResponse
    {
        $this->modeloService->eliminar($request, $modelo);

        return response()->json(['message' => 'Modelo eliminado correctamente.']);
    }

    public function dataAtributos(Request $request): JsonResponse
    {
        $atributos = $this->atributoService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $atributos->map(fn ($item) => [
            'atr_id' => $item->atr_id,
            'atr_nombre' => $item->atr_nombre,
            'atr_clave' => $item->atr_clave,
            'atr_tipo' => $item->atr_tipo,
            'atr_estatus' => $item->atr_estatus,
            'valores_total' => $item->valores_total,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function showAtributo(int $atributo): JsonResponse
    {
        $item = \App\Models\Atributo::query()->findOrFail($atributo);

        return response()->json(['data' => [
            'atr_id' => $item->atr_id,
            'atr_nombre' => $item->atr_nombre,
            'atr_clave' => $item->atr_clave,
            'atr_tipo' => $item->atr_tipo,
            'atr_estatus' => $item->atr_estatus,
        ]]);
    }

    public function storeAtributo(StoreAtributoRequest $request): JsonResponse
    {
        $item = $this->atributoService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Atributo creado correctamente.',
            'data' => ['atr_id' => $item->atr_id],
        ]);
    }

    public function updateAtributo(UpdateAtributoRequest $request, int $atributo): JsonResponse
    {
        $this->atributoService->actualizar($request, $atributo, $request->validated());

        return response()->json(['message' => 'Atributo actualizado correctamente.']);
    }

    public function cambiarEstatusAtributo(Request $request, int $atributo): JsonResponse
    {
        $request->validate([
            'atr_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'atr_estatus.required' => 'El estatus es obligatorio.',
            'atr_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->atributoService->cambiarEstatus($request, $atributo, $request->string('atr_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de atributo actualizado correctamente.',
            'data' => ['atr_estatus' => $registro->atr_estatus],
        ]);
    }

    public function eliminarAtributo(Request $request, int $atributo): JsonResponse
    {
        $this->atributoService->eliminar($request, $atributo);

        return response()->json(['message' => 'Atributo eliminado correctamente.']);
    }

    public function dataValoresAtributo(Request $request): JsonResponse
    {
        $valores = $this->atributoService->listarValores([
            'vat_atr_id' => $request->query('vat_atr_id'),
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $valores->map(fn ($item) => [
            'vat_id' => $item->vat_id,
            'vat_atr_id' => $item->vat_atr_id,
            'vat_valor' => $item->vat_valor,
            'vat_clave' => $item->vat_clave,
            'vat_estatus' => $item->vat_estatus,
            'atributo' => $item->atributo?->atr_nombre,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function showValorAtributo(int $valor): JsonResponse
    {
        $item = \App\Models\ValorAtributo::query()->findOrFail($valor);

        return response()->json(['data' => [
            'vat_id' => $item->vat_id,
            'vat_atr_id' => $item->vat_atr_id,
            'vat_valor' => $item->vat_valor,
            'vat_clave' => $item->vat_clave,
            'vat_estatus' => $item->vat_estatus,
        ]]);
    }

    public function storeValorAtributo(StoreValorAtributoRequest $request): JsonResponse
    {
        $item = $this->atributoService->crearValor($request, $request->validated());

        return response()->json([
            'message' => 'Valor de atributo creado correctamente.',
            'data' => ['vat_id' => $item->vat_id],
        ]);
    }

    public function updateValorAtributo(UpdateValorAtributoRequest $request, int $valor): JsonResponse
    {
        $this->atributoService->actualizarValor($request, $valor, $request->validated());

        return response()->json(['message' => 'Valor de atributo actualizado correctamente.']);
    }

    public function cambiarEstatusValorAtributo(Request $request, int $valor): JsonResponse
    {
        $request->validate([
            'vat_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'vat_estatus.required' => 'El estatus es obligatorio.',
            'vat_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->atributoService->cambiarEstatusValor($request, $valor, $request->string('vat_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de valor actualizado correctamente.',
            'data' => ['vat_estatus' => $registro->vat_estatus],
        ]);
    }

    public function eliminarValorAtributo(Request $request, int $valor): JsonResponse
    {
        $this->atributoService->eliminarValor($request, $valor);

        return response()->json(['message' => 'Valor de atributo eliminado correctamente.']);
    }

    public function dataProductos(Request $request): JsonResponse
    {
        $productos = $this->productoService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $productos->map(fn ($item) => [
            'prd_id' => $item->prd_id,
            'prd_codigo' => $item->prd_codigo,
            'prd_codigo_barras' => $item->prd_codigo_barras,
            'prd_clave_sat' => $item->prd_clave_sat,
            'prd_nombre' => $item->prd_nombre,
            'prd_descripcion' => $item->prd_descripcion,
            'prd_tipo' => $item->prd_tipo,
            'prd_precio_base' => $item->prd_precio_base,
            'prd_costo' => $item->prd_costo,
            'prd_stock_minimo' => $item->prd_stock_minimo,
            'prd_stock_maximo' => $item->prd_stock_maximo,
            'prd_estatus' => $item->prd_estatus,
            'marca' => $item->marca?->mrc_nombre,
            'proveedor' => $item->proveedor?->prv_nombre_empresa,
            'linea' => $item->linea?->lna_nombre,
            'categoria' => $item->categoria?->ctg_nombre,
            'unidad' => $item->unidad?->umd_nombre,
            'almacenes_permitidos' => $item->almacenesPermitidos->map(fn ($almacen) => [
                'alm_id' => $almacen->alm_id,
                'alm_nombre' => $almacen->alm_nombre,
            ])->values(),
            'imagen_preview_url' => $this->resolverUrlImagenProducto($item->prd_imagen_tipo, $item->prd_imagen_path, $item->prd_imagen_url),
            'atributos' => $item->atributos->pluck('atr_nombre')->values(),
            'skus_total' => $item->skus_total,
            'skus_activos' => $item->skus_activos,
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function showProducto(int $producto): JsonResponse
    {
        $item = $this->productoService->obtenerPorId($producto);

        return response()->json(['data' => $this->mapProductoPayload($item)]);
    }

    public function storeProducto(StoreProductoRequest $request): JsonResponse
    {
        $item = $this->productoService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data' => ['prd_id' => $item->prd_id],
        ]);
    }

    public function updateProducto(UpdateProductoRequest $request, int $producto): JsonResponse
    {
        $this->productoService->actualizar($request, $producto, $request->validated());

        return response()->json(['message' => 'Producto actualizado correctamente.']);
    }

    public function cambiarEstatusProducto(Request $request, int $producto): JsonResponse
    {
        $request->validate([
            'prd_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'prd_estatus.required' => 'El estatus es obligatorio.',
            'prd_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->productoService->cambiarEstatus($request, $producto, $request->string('prd_estatus')->toString());

        return response()->json([
            'message' => 'Estatus del producto actualizado correctamente.',
            'data' => ['prd_estatus' => $registro->prd_estatus],
        ]);
    }

    public function eliminarProducto(Request $request, int $producto): JsonResponse
    {
        $this->productoService->eliminar($request, $producto);

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }

    public function dataSkus(Request $request): JsonResponse
    {
        $skus = $this->productoSkuService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
            'psk_prd_id' => $request->query('psk_prd_id'),
            'atributo_filtros' => $request->query('atributo_filtros', []),
        ]);

        $data = $skus->map(function ($item): array {
            $combinacion = $item->valoresAtributo
                ->map(fn ($valor) => ($valor->atributo?->atr_nombre ?? 'Atributo') . ': ' . $valor->vat_valor)
                ->values();

            return [
                'psk_id' => $item->psk_id,
                'psk_prd_id' => $item->psk_prd_id,
                'psk_codigo' => $item->psk_codigo,
                'psk_codigo_barras' => $item->psk_codigo_barras,
                'psk_nombre' => $item->psk_nombre,
                'psk_precio' => $item->psk_precio,
                'psk_costo' => $item->psk_costo,
                'psk_stock_minimo' => $item->psk_stock_minimo,
                'psk_stock_maximo' => $item->psk_stock_maximo,
                'psk_estatus' => $item->psk_estatus,
                'producto' => $item->producto?->prd_nombre,
                'producto_codigo' => $item->producto?->prd_codigo,
                'valor_atributo_ids' => $item->valoresAtributo->pluck('vat_id')->values(),
                'combinacion' => $combinacion,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function dataSkusAgrupados(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'psk_prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'buscar',
            'estatus',
        ]);

        $atributoFiltros = $request->input('atributo_filtros', []);
        if (is_array($atributoFiltros)) {
            $filtros['atributo_filtros'] = $atributoFiltros;
        }

        $rows = collect($this->productoSkuService->listarAgrupado($filtros))
            ->map(function (array $grupo) use ($filtros): array {
                if (!empty($filtros['estatus'])) {
                    $grupo['variantes'] = array_values(array_filter($grupo['variantes'] ?? [], function (array $variante) use ($filtros): bool {
                        return (string) ($variante['psk_estatus'] ?? '') === (string) $filtros['estatus'];
                    }));
                }

                return $grupo;
            })
            ->filter(fn (array $grupo) => !empty($grupo['variantes']))
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function filtrosSkus(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psk_prd_id' => ['required', 'integer', Rule::exists('tbl_productos_prd', 'prd_id')],
        ], [
            'psk_prd_id.required' => 'El producto es obligatorio.',
            'psk_prd_id.exists' => 'El producto seleccionado no existe.',
        ]);

        return response()->json([
            'data' => $this->productoSkuService->obtenerFiltrosProducto((int) $datos['psk_prd_id']),
        ]);
    }

    public function matrizSkus(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psk_prd_id' => ['required', 'integer', Rule::exists('tbl_productos_prd', 'prd_id')],
        ], [
            'psk_prd_id.required' => 'El producto es obligatorio.',
            'psk_prd_id.exists' => 'El producto seleccionado no existe.',
        ]);

        return response()->json([
            'data' => $this->productoSkuService->obtenerMatrizProducto(
                (int) $datos['psk_prd_id'],
                (array) $request->query('atributo_filtros', [])
            ),
        ]);
    }

    public function showSku(int $sku): JsonResponse
    {
        $item = $this->productoSkuService->obtenerPorId($sku);

        $combinacion = $item->valoresAtributo
            ->map(fn ($valor) => ($valor->atributo?->atr_nombre ?? 'Atributo') . ': ' . $valor->vat_valor)
            ->values();

        return response()->json(['data' => [
            'psk_id' => $item->psk_id,
            'psk_prd_id' => $item->psk_prd_id,
            'psk_codigo' => $item->psk_codigo,
            'psk_codigo_barras' => $item->psk_codigo_barras,
            'psk_nombre' => $item->psk_nombre,
            'psk_precio' => $item->psk_precio,
            'psk_costo' => $item->psk_costo,
            'psk_stock_minimo' => $item->psk_stock_minimo,
            'psk_stock_maximo' => $item->psk_stock_maximo,
            'psk_estatus' => $item->psk_estatus,
            'valor_atributo_ids' => $item->valoresAtributo->pluck('vat_id')->values(),
            'combinacion' => $combinacion,
        ]]);
    }

    public function updateSku(UpdateProductoSkuRequest $request, int $sku): JsonResponse
    {
        $this->productoSkuService->actualizar($request, $sku, $request->validated());

        return response()->json(['message' => 'SKU actualizado correctamente.']);
    }

    public function cambiarEstatusSku(Request $request, int $sku): JsonResponse
    {
        $request->validate([
            'psk_estatus' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'psk_estatus.required' => 'El estatus es obligatorio.',
            'psk_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->productoSkuService->cambiarEstatus($request, $sku, $request->string('psk_estatus')->toString());

        return response()->json([
            'message' => 'Estatus del SKU actualizado correctamente.',
            'data' => ['psk_estatus' => $registro->psk_estatus],
        ]);
    }

    public function eliminarSku(Request $request, int $sku): JsonResponse
    {
        $this->productoSkuService->eliminar($request, $sku);

        return response()->json(['message' => 'SKU eliminado correctamente.']);
    }

    private function submenus(): array
    {
        return [
            ['key' => 'base', 'label' => 'Catálogos base', 'route' => route('desktop.operacion.catalogo_comercial.base.index')],
            ['key' => 'atributos', 'label' => 'Atributos', 'route' => route('desktop.operacion.catalogo_comercial.atributos.index')],
            ['key' => 'productos', 'label' => 'Productos', 'route' => route('desktop.operacion.catalogo_comercial.productos.index')],
            ['key' => 'skus', 'label' => 'SKU / Variantes', 'route' => route('desktop.operacion.catalogo_comercial.skus.index')],
            ['key' => 'proveedores', 'label' => 'Proveedores', 'route' => route('desktop.operacion.catalogo_comercial.proveedores.index')],
            ['key' => 'etiquetado', 'label' => 'Etiquetado', 'route' => route('desktop.operacion.catalogo_comercial.etiquetado.index')],
        ];
    }

    private function catalogTypes(): array
    {
        return [
            ['key' => 'marcas', 'label' => 'Marcas'],
            ['key' => 'modelos', 'label' => 'Modelos'],
            ['key' => 'lineas', 'label' => 'Líneas'],
            ['key' => 'categorias', 'label' => 'Conceptos'],
            ['key' => 'unidades', 'label' => 'Unidades'],
            ['key' => 'motivos', 'label' => 'Motivos'],
        ];
    }

    private function permisosUI(): array
    {
        return [
            'crear' => auth()->user()?->tienePermiso('catalogo_comercial.crear') ?? false,
            'editar' => auth()->user()?->tienePermiso('catalogo_comercial.editar') ?? false,
            'inactivar' => auth()->user()?->tienePermiso('catalogo_comercial.inactivar') ?? false,
            'eliminar' => auth()->user()?->tienePermiso('catalogo_comercial.eliminar') ?? false,
        ];
    }

    private function renderPlaceholder(string $activeSubmenu, string $pageTitle, string $pageDescription)
    {
        return view('desktop.operacion.catalogo_comercial.placeholder', [
            'activeSubmenu' => $activeSubmenu,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'submenus' => $this->submenus(),
        ]);
    }

    private function validarTipoCatalogo(string $tipo): void
    {
        abort_unless(in_array($tipo, ['marcas', 'lineas', 'categorias', 'unidades', 'conceptos', 'motivos'], true), 404);
    }

    private function idColumnCatalogo(string $tipo): string
    {
        return match ($tipo) {
            'marcas' => 'mrc_id',
            'lineas' => 'lna_id',
            'categorias' => 'ctg_id',
            'unidades' => 'umd_id',
            'conceptos' => 'cpt_id',
            'motivos' => 'mtv_id',
        };
    }

    private function estatusColumnCatalogo(string $tipo): string
    {
        return match ($tipo) {
            'marcas' => 'mrc_estatus',
            'lineas' => 'lna_estatus',
            'categorias' => 'ctg_estatus',
            'unidades' => 'umd_estatus',
            'conceptos' => 'cpt_estatus',
            'motivos' => 'mtv_estatus',
        };
    }

    private function resolverUrlImagenProducto(?string $tipo, ?string $path, ?string $url): ?string
    {
        if ($tipo === 'archivo' && $path) {
            return '/storage/' . ltrim($path, '/');
        }

        return $url;
    }

    private function productoFormViewData(?Producto $producto = null): array
    {
        $opcionesProducto = $this->productoService->opcionesParaFormulario();
        $atributos = $this->atributoService->opcionesAtributosActivos();
        $valores = $this->atributoService->opcionesValoresActivos();
        $modelos = $this->modeloService->opcionesActivas();

        return [
            'submenus' => $this->submenus(),
            'opciones' => [
                'marcas' => $opcionesProducto['marcas'],
                'lineas' => $opcionesProducto['lineas'],
                'categorias' => $opcionesProducto['categorias'],
                'unidades' => $opcionesProducto['unidades'],
                'almacenes' => $opcionesProducto['almacenes'],
                'proveedores' => $opcionesProducto['proveedores'],
                'atributos' => $atributos,
                'valores' => $valores,
                'modelos' => $modelos,
            ],
            'valoresCatalogo' => $valores
                ->map(fn ($item) => [
                    'vat_id' => $item->vat_id,
                    'vat_atr_id' => $item->vat_atr_id,
                    'vat_valor' => $item->vat_valor,
                ])
                ->values(),
            'atributosCatalogo' => $atributos
                ->map(fn ($item) => [
                    'atr_id' => $item->atr_id,
                    'atr_nombre' => $item->atr_nombre,
                ])
                ->values(),
            'unidadesCatalogo' => $opcionesProducto['unidades']
                ->map(fn ($item) => [
                    'id' => $item->umd_id,
                    'nombre' => $item->umd_nombre,
                    'codigo' => $item->umd_codigo,
                    'es_predeterminada' => (bool) $item->umd_es_predeterminada,
                ])
                ->values(),
            'modelosCatalogo' => $modelos
                ->map(fn ($item) => [
                    'mdl_id' => $item->mdl_id,
                    'mdl_nombre' => $item->mdl_nombre,
                    'mdl_clave' => $item->mdl_clave,
                    'marca_ids' => $item->marcas
                        ->pluck('mrc_id')
                        ->map(fn ($id) => (int) $id)
                        ->values(),
                ])
                ->values(),
            'categoriasCatalogo' => $opcionesProducto['categorias']
                ->map(fn ($item) => [
                    'ctg_id' => $item->ctg_id,
                    'ctg_nombre' => $item->ctg_nombre,
                    'ctg_lna_id' => $item->ctg_lna_id ?? null,
                ])
                ->values(),
            'initialProductoData' => $producto ? $this->mapProductoPayload($producto) : null,
            'isEditing' => (bool) $producto,
            'permisosUI' => $this->permisosUI(),
        ];
    }

    private function mapProductoPayload(Producto $item): array
    {
        return [
            'prd_id' => $item->prd_id,
            'prd_codigo' => $item->prd_codigo,
            'prd_codigo_barras' => $item->prd_codigo_barras,
            'prd_clave_sat' => $item->prd_clave_sat,
            'prd_nombre' => $item->prd_nombre,
            'prd_descripcion' => $item->prd_descripcion,
            'prd_precio_base' => $item->prd_precio_base,
            'prd_costo' => $item->prd_costo,
            'prd_stock_minimo' => $item->prd_stock_minimo,
            'prd_stock_maximo' => $item->prd_stock_maximo,
            'prd_mrc_id' => $item->prd_mrc_id,
            'prd_mdl_id' => $item->prd_mdl_id,
            'prd_prv_id' => $item->prd_prv_id,
            'prd_lna_id' => $item->prd_lna_id,
            'prd_ctg_id' => $item->prd_ctg_id,
            'prd_umd_id' => $item->prd_umd_id,
            'almacen_ids' => $item->almacenesPermitidos->pluck('alm_id')->map(fn ($id) => (int) $id)->values(),
            'prd_tipo' => $item->prd_tipo,
            'prd_estatus' => $item->prd_estatus,
            'prd_imagen_tipo' => $item->prd_imagen_tipo,
            'prd_imagen_url' => $item->prd_imagen_url,
            'prd_imagen_preview_url' => $this->resolverUrlImagenProducto($item->prd_imagen_tipo, $item->prd_imagen_path, $item->prd_imagen_url),
            'atributo_ids' => $item->atributos->pluck('atr_id')->values(),
            'atributo_valores' => $item->getAttribute('atributo_valores'),
            'corridas' => $this->productoService->obtenerCorridasProducto($item),
        ];
    }

    private function skuViewData(): array
    {
        $opcionesProducto = $this->productoService->opcionesParaFormulario();
        $modelos = $this->modeloService->opcionesActivas();

        return [
            'submenus' => $this->submenus(),
            'permisosUI' => $this->permisosUI(),
            'opciones' => [
                'productos' => $opcionesProducto['productos'],
                'marcas' => $opcionesProducto['marcas'],
                'lineas' => $opcionesProducto['lineas'],
                'categorias' => $opcionesProducto['categorias'],
                'modelos' => $modelos,
            ],
        ];
    }
}
