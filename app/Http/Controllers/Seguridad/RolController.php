<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreRolRequest;
use App\Http\Requests\Seguridad\UpdateRolRequest;
use App\Services\PermisoService;
use App\Services\RolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private readonly RolService $rolService,
        private readonly PermisoService $permisoService,
    ) {
    }

    public function index()
    {
        return view('seguridad.roles.index', [
            'permisos' => $this->permisoService->listar(),
        ]);
    }

    public function data(): JsonResponse
    {
        $roles = $this->rolService->listar();

        $data = $roles->map(function ($rol): array {
            return [
                'rol_id' => $rol->rol_id,
                'rol_nombre' => $rol->rol_nombre,
                'rol_descripcion' => $rol->rol_descripcion,
                'rol_estatus' => $rol->rol_estatus,
                'permisos' => $rol->permisos->map(fn ($permiso) => [
                    'clave' => $permiso->prm_clave,
                    'descripcion' => $permiso->prm_descripcion,
                ])->values(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function show(int $rol): JsonResponse
    {
        $registro = $this->rolService->obtenerPorId($rol);

        return response()->json([
            'data' => [
                'rol_id' => $registro->rol_id,
                'rol_nombre' => $registro->rol_nombre,
                'rol_descripcion' => $registro->rol_descripcion,
                'rol_estatus' => $registro->rol_estatus,
                'permisos' => $registro->permisos()->pluck('tbl_permisos_prm.prm_id')->values(),
            ],
        ]);
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        $rol = $this->rolService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Rol creado correctamente.',
            'data' => ['rol_id' => $rol->rol_id],
        ]);
    }

    public function update(UpdateRolRequest $request, int $rol): JsonResponse
    {
        $this->rolService->actualizar($request, $rol, $request->validated());

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
        ]);
    }

    public function cambiarEstatus(Request $request, int $rol): JsonResponse
    {
        $request->validate([
            'rol_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'rol_estatus.required' => 'El estatus es obligatorio.',
            'rol_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->rolService->cambiarEstatus($request, $rol, $request->string('rol_estatus')->toString());

        return response()->json([
            'message' => 'Estatus de rol actualizado correctamente.',
            'data' => ['rol_estatus' => $registro->rol_estatus],
        ]);
    }
}
