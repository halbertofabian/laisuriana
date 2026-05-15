<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\PermisoService;
use Illuminate\Http\JsonResponse;

class PermisoController extends Controller
{
    public function __construct(private readonly PermisoService $permisoService)
    {
    }

    public function index()
    {
        return view('seguridad.permisos.index');
    }

    public function data(): JsonResponse
    {
        $permisos = $this->permisoService->listar();

        $data = $permisos->map(function ($permiso): array {
            return [
                'prm_id' => $permiso->prm_id,
                'prm_clave' => $permiso->prm_clave,
                'prm_descripcion' => $permiso->prm_descripcion,
                'prm_modulo' => $permiso->prm_modulo,
                'prm_estatus' => $permiso->prm_estatus,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
