<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreCajaRequest;
use App\Http\Requests\Operacion\UpdateCajaRequest;
use App\Models\Almacen;
use App\Models\Usuario;
use App\Services\Operacion\CajaService;
use App\Services\Operacion\SucursalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function __construct(
        private readonly CajaService $cajaService,
        private readonly SucursalService $sucursalService,
    ) {
    }

    public function index()
    {
        $usuariosActivos = Usuario::query()
            ->where('usr_estatus', 'activo')
            ->orderBy('usr_nombre')
            ->get(['usr_id', 'usr_nombre', 'usr_usuario']);
        $almacenesActivos = Almacen::query()
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_scl_id')
            ->orderBy('alm_nombre')
            ->get(['alm_id', 'alm_scl_id', 'alm_nombre']);
        $almacenesActivosJs = $almacenesActivos->map(fn ($a) => [
            'alm_id' => (int) $a->alm_id,
            'alm_scl_id' => (int) $a->alm_scl_id,
            'alm_nombre' => (string) $a->alm_nombre,
        ])->values();

        return view('operacion.cajas.index', [
            'opciones' => [
                'sucursales' => $this->sucursalService->opcionesActivas(),
                'usuarios' => $usuariosActivos,
                'almacenes' => $almacenesActivos,
                'almacenes_js' => $almacenesActivosJs,
            ],
            'permisosUI' => [
                'caja_crear' => auth()->user()?->tienePermiso('caja.crear') ?? false,
                'caja_editar' => auth()->user()?->tienePermiso('caja.editar') ?? false,
                'caja_inactivar' => auth()->user()?->tienePermiso('caja.inactivar') ?? false,
                'caja_eliminar' => auth()->user()?->tienePermiso('caja.eliminar') ?? false,
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $cajas = $this->cajaService->listar([
            'buscar' => $request->query('buscar'),
            'estatus' => $request->query('estatus'),
            'caj_scl_id' => $request->query('caj_scl_id'),
        ]);

        $data = $cajas->map(function ($caja): array {
            return [
                'caj_id' => $caja->caj_id,
                'caj_nombre' => $caja->caj_nombre,
                'caj_clave' => $caja->caj_clave,
                'caj_estatus' => $caja->caj_estatus,
                'caj_scl_id' => $caja->caj_scl_id,
                'caj_alm_id' => $caja->caj_alm_id,
                'caj_retiro_umbral' => (float) ($caja->caj_retiro_umbral ?? 0),
                'sucursal' => $caja->sucursal?->scl_nombre,
                'almacen' => $caja->almacen?->alm_nombre,
                'usuarios' => $caja->usuarios->map(fn ($u) => [
                    'usr_id' => $u->usr_id,
                    'usr_nombre' => $u->usr_nombre,
                    'usr_usuario' => $u->usr_usuario,
                ])->values(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function show(int $caja): JsonResponse
    {
        $registro = $this->cajaService->obtenerPorId($caja);

        return response()->json([
            'data' => [
                'caj_id' => $registro->caj_id,
                'caj_scl_id' => $registro->caj_scl_id,
                'caj_nombre' => $registro->caj_nombre,
                'caj_clave' => $registro->caj_clave,
                'caj_estatus' => $registro->caj_estatus,
                'caj_alm_id' => $registro->caj_alm_id,
                'caj_retiro_umbral' => (float) ($registro->caj_retiro_umbral ?? 0),
                'usuarios' => $registro->usuarios->pluck('usr_id')->values(),
            ],
        ]);
    }

    public function store(StoreCajaRequest $request): JsonResponse
    {
        $caja = $this->cajaService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Caja creada correctamente.',
            'data' => ['caj_id' => $caja->caj_id],
        ]);
    }

    public function update(UpdateCajaRequest $request, int $caja): JsonResponse
    {
        $this->cajaService->actualizar($request, $caja, $request->validated());

        return response()->json([
            'message' => 'Caja actualizada correctamente.',
        ]);
    }

    public function cambiarEstatus(Request $request, int $caja): JsonResponse
    {
        $request->validate([
            'caj_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'caj_estatus.required' => 'El estatus es obligatorio.',
            'caj_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->cajaService->cambiarEstatus($request, $caja, $request->string('caj_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de caja actualizado correctamente.',
            'data' => ['caj_estatus' => $registro->caj_estatus],
        ]);
    }

    public function eliminar(Request $request, int $caja): JsonResponse
    {
        $this->cajaService->eliminar($request, $caja);

        return response()->json([
            'message' => 'Caja eliminada correctamente.',
        ]);
    }
}
