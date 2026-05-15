<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreAlmacenRequest;
use App\Http\Requests\Operacion\StoreSucursalRequest;
use App\Http\Requests\Operacion\StoreTipoAlmacenRequest;
use App\Http\Requests\Operacion\UpdateAlmacenRequest;
use App\Http\Requests\Operacion\UpdateSucursalRequest;
use App\Http\Requests\Operacion\UpdateTipoAlmacenRequest;
use App\Services\Operacion\AlmacenService;
use App\Services\Operacion\SucursalService;
use App\Services\Operacion\TipoAlmacenService;
use App\Models\Almacen;
use App\Models\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalAlmacenController extends Controller
{
    public function __construct(
        private readonly SucursalService $sucursalService,
        private readonly AlmacenService $almacenService,
        private readonly TipoAlmacenService $tipoAlmacenService,
    ) {
    }

    public function index()
    {
        return redirect()->route('operacion.sucursales_almacenes.sucursales.index');
    }

    public function sucursales()
    {
        return $this->renderVista('sucursales');
    }

    public function almacenes()
    {
        return $this->renderVista('almacenes');
    }

    public function tipos()
    {
        return $this->renderVista('tipos');
    }

    private function renderVista(string $vistaActiva)
    {
        return view('operacion.sucursales_almacenes.index', [
            'vistaActiva' => $vistaActiva,
            'opciones' => [
                'sucursales' => $this->sucursalService->opcionesActivas(),
                'tipos_almacen' => $this->tipoAlmacenService->opcionesActivas(),
            ],
            'permisosUI' => [
                'sucursal_crear' => auth()->user()?->tienePermiso('sucursal.crear') ?? false,
                'sucursal_editar' => auth()->user()?->tienePermiso('sucursal.editar') ?? false,
                'sucursal_inactivar' => auth()->user()?->tienePermiso('sucursal.inactivar') ?? false,
                'sucursal_eliminar' => auth()->user()?->tienePermiso('sucursal.eliminar') ?? false,
                'almacen_crear' => auth()->user()?->tienePermiso('almacen.crear') ?? false,
                'almacen_editar' => auth()->user()?->tienePermiso('almacen.editar') ?? false,
                'almacen_inactivar' => auth()->user()?->tienePermiso('almacen.inactivar') ?? false,
                'almacen_eliminar' => auth()->user()?->tienePermiso('almacen.eliminar') ?? false,
                'tipo_crear' => auth()->user()?->tienePermiso('tipo_almacen.crear') ?? false,
                'tipo_editar' => auth()->user()?->tienePermiso('tipo_almacen.editar') ?? false,
                'tipo_inactivar' => auth()->user()?->tienePermiso('tipo_almacen.inactivar') ?? false,
                'tipo_eliminar' => auth()->user()?->tienePermiso('tipo_almacen.eliminar') ?? false,
            ],
        ]);
    }

    public function dataSucursales(Request $request): JsonResponse
    {
        $sucursales = $this->sucursalService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $sucursales->map(function ($sucursal): array {
            return [
                'scl_id' => $sucursal->scl_id,
                'scl_nombre' => $sucursal->scl_nombre,
                'scl_clave' => $sucursal->scl_clave,
                'scl_estatus' => $sucursal->scl_estatus,
                'almacenes_total' => $sucursal->almacenes_total,
                'almacenes_activos' => $sucursal->almacenes_activos,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showSucursal(int $sucursal): JsonResponse
    {
        $registro = $this->sucursalService->obtenerPorId($sucursal);

        return response()->json([
            'data' => [
                'scl_id' => $registro->scl_id,
                'scl_nombre' => $registro->scl_nombre,
                'scl_clave' => $registro->scl_clave,
                'scl_estatus' => $registro->scl_estatus,
            ],
        ]);
    }

    public function storeSucursal(StoreSucursalRequest $request): JsonResponse
    {
        $sucursal = $this->sucursalService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'data' => ['scl_id' => $sucursal->scl_id],
        ]);
    }

    public function updateSucursal(UpdateSucursalRequest $request, int $sucursal): JsonResponse
    {
        $this->sucursalService->actualizar($request, $sucursal, $request->validated());

        return response()->json([
            'message' => 'Sucursal actualizada correctamente.',
        ]);
    }

    public function cambiarEstatusSucursal(Request $request, int $sucursal): JsonResponse
    {
        $request->validate([
            'scl_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'scl_estatus.required' => 'El estatus es obligatorio.',
            'scl_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->sucursalService->cambiarEstatus($request, $sucursal, $request->string('scl_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de sucursal actualizado correctamente.',
            'data' => ['scl_estatus' => $registro->scl_estatus],
        ]);
    }

    public function eliminarSucursal(Request $request, int $sucursal): JsonResponse
    {
        $this->sucursalService->eliminar($request, $sucursal);

        return response()->json([
            'message' => 'Sucursal eliminada correctamente.',
        ]);
    }

    public function dataAlmacenes(Request $request): JsonResponse
    {
        $almacenes = $this->almacenService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
            'alm_scl_id' => $request->query('alm_scl_id'),
            'alm_tal_id' => $request->query('alm_tal_id'),
        ]);

        $data = $almacenes->map(function ($almacen): array {
            return [
                'alm_id' => $almacen->alm_id,
                'alm_nombre' => $almacen->alm_nombre,
                'alm_clave' => $almacen->alm_clave,
                'alm_estatus' => $almacen->alm_estatus,
                'sucursal' => $almacen->sucursal?->scl_nombre,
                'tipo' => $almacen->tipo?->tal_nombre,
                'alm_scl_id' => $almacen->alm_scl_id,
                'alm_tal_id' => $almacen->alm_tal_id,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function showAlmacen(int $almacen): JsonResponse
    {
        $registro = $this->almacenService->obtenerPorId($almacen);

        return response()->json([
            'data' => [
                'alm_id' => $registro->alm_id,
                'alm_scl_id' => $registro->alm_scl_id,
                'alm_tal_id' => $registro->alm_tal_id,
                'alm_nombre' => $registro->alm_nombre,
                'alm_clave' => $registro->alm_clave,
                'alm_estatus' => $registro->alm_estatus,
            ],
        ]);
    }

    public function storeAlmacen(StoreAlmacenRequest $request): JsonResponse
    {
        $almacen = $this->almacenService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Almacén creado correctamente.',
            'data' => ['alm_id' => $almacen->alm_id],
        ]);
    }

    public function updateAlmacen(UpdateAlmacenRequest $request, int $almacen): JsonResponse
    {
        $this->almacenService->actualizar($request, $almacen, $request->validated());

        return response()->json([
            'message' => 'Almacén actualizado correctamente.',
        ]);
    }

    public function cambiarEstatusAlmacen(Request $request, int $almacen): JsonResponse
    {
        $request->validate([
            'alm_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'alm_estatus.required' => 'El estatus es obligatorio.',
            'alm_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->almacenService->cambiarEstatus($request, $almacen, $request->string('alm_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de almacén actualizado correctamente.',
            'data' => ['alm_estatus' => $registro->alm_estatus],
        ]);
    }

    public function eliminarAlmacen(Request $request, int $almacen): JsonResponse
    {
        $this->almacenService->eliminar($request, $almacen);

        return response()->json([
            'message' => 'Almacén eliminado correctamente.',
        ]);
    }

    public function dataTiposAlmacen(Request $request): JsonResponse
    {
        $tipos = $this->tipoAlmacenService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
        ]);

        $data = $tipos->map(function ($tipo): array {
            return [
                'tal_id' => $tipo->tal_id,
                'tal_nombre' => $tipo->tal_nombre,
                'tal_clave' => $tipo->tal_clave,
                'tal_descripcion' => $tipo->tal_descripcion,
                'tal_estatus' => $tipo->tal_estatus,
                'almacenes_total' => $tipo->almacenes_total,
                'almacenes_activos' => $tipo->almacenes_activos,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function sucursalesMobile(): JsonResponse
    {
        $data = Sucursal::query()
            ->select(['scl_id', 'scl_nombre', 'scl_clave'])
            ->where('scl_estatus', 'activo')
            ->orderBy('scl_nombre')
            ->get()
            ->values();

        return response()->json(['data' => $data]);
    }

    public function almacenesMobile(Request $request): JsonResponse
    {
        $sucursalId = (int) $request->query('scl_id', 0);

        $query = Almacen::query()
            ->select(['alm_id', 'alm_scl_id', 'alm_nombre', 'alm_clave'])
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_nombre');

        if ($sucursalId > 0) {
            $query->where('alm_scl_id', $sucursalId);
        }

        return response()->json(['data' => $query->get()->values()]);
    }

    public function showTipoAlmacen(int $tipo_almacen): JsonResponse
    {
        $registro = $this->tipoAlmacenService->obtenerPorId($tipo_almacen);

        return response()->json([
            'data' => [
                'tal_id' => $registro->tal_id,
                'tal_nombre' => $registro->tal_nombre,
                'tal_clave' => $registro->tal_clave,
                'tal_descripcion' => $registro->tal_descripcion,
                'tal_estatus' => $registro->tal_estatus,
            ],
        ]);
    }

    public function storeTipoAlmacen(StoreTipoAlmacenRequest $request): JsonResponse
    {
        $tipo = $this->tipoAlmacenService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Tipo de almacén creado correctamente.',
            'data' => ['tal_id' => $tipo->tal_id],
        ]);
    }

    public function updateTipoAlmacen(UpdateTipoAlmacenRequest $request, int $tipo_almacen): JsonResponse
    {
        $this->tipoAlmacenService->actualizar($request, $tipo_almacen, $request->validated());

        return response()->json([
            'message' => 'Tipo de almacén actualizado correctamente.',
        ]);
    }

    public function cambiarEstatusTipoAlmacen(Request $request, int $tipo_almacen): JsonResponse
    {
        $request->validate([
            'tal_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'tal_estatus.required' => 'El estatus es obligatorio.',
            'tal_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->tipoAlmacenService->cambiarEstatus($request, $tipo_almacen, $request->string('tal_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de tipo de almacén actualizado correctamente.',
            'data' => ['tal_estatus' => $registro->tal_estatus],
        ]);
    }

    public function eliminarTipoAlmacen(Request $request, int $tipo_almacen): JsonResponse
    {
        $this->tipoAlmacenService->eliminar($request, $tipo_almacen);

        return response()->json([
            'message' => 'Tipo de almacén eliminado correctamente.',
        ]);
    }
}
