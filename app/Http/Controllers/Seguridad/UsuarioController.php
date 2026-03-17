<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreUsuarioRequest;
use App\Http\Requests\Seguridad\UpdateUsuarioRequest;
use App\Models\UsuarioSucursal;
use App\Services\PermisoService;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
        private readonly PermisoService $permisoService,
    ) {
    }

    public function index()
    {
        return view('seguridad.usuarios.index', [
            'opciones' => $this->usuarioService->opcionesParaFormulario(),
            'permisos' => $this->permisoService->listar(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $usuarios = $this->usuarioService->listar([
            'buscar' => $request->query('buscar'),
        ]);

        $data = $usuarios->map(function ($usuario): array {
            return [
                'usr_id' => $usuario->usr_id,
                'usr_nombre' => $usuario->usr_nombre,
                'usr_usuario' => $usuario->usr_usuario,
                'usr_email' => $usuario->usr_email,
                'usr_estatus' => $usuario->usr_estatus,
                'roles' => $usuario->roles->pluck('rol_nombre')->values(),
                'sucursales' => $usuario->sucursales->pluck('scl_nombre')->values(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function show(int $usuario): JsonResponse
    {
        $registro = $this->usuarioService->obtenerPorId($usuario);
        $sucursalPredeterminadaId = UsuarioSucursal::query()
            ->where('usc_usr_id', $registro->usr_id)
            ->where('usc_deleted', false)
            ->whereNull('usc_deleted_at')
            ->where('usc_estatus', 'activo')
            ->where('usc_es_predeterminada', true)
            ->value('usc_scl_id');

        return response()->json([
            'data' => [
                'usr_id' => $registro->usr_id,
                'usr_nombre' => $registro->usr_nombre,
                'usr_usuario' => $registro->usr_usuario,
                'usr_email' => $registro->usr_email,
                'usr_estatus' => $registro->usr_estatus,
                'roles' => $registro->roles()->pluck('tbl_roles_rol.rol_id')->values(),
                'sucursales' => $registro->sucursales()->pluck('tbl_sucursales_scl.scl_id')->values(),
                'usc_scl_predeterminada' => $sucursalPredeterminadaId,
            ],
        ]);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->usuarioService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => ['usr_id' => $usuario->usr_id],
        ]);
    }

    public function update(UpdateUsuarioRequest $request, int $usuario): JsonResponse
    {
        $this->usuarioService->actualizar($request, $usuario, $request->validated());

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function cambiarEstatus(Request $request, int $usuario): JsonResponse
    {
        $request->validate([
            'usr_estatus' => ['required', 'in:activo,inactivo'],
        ], [
            'usr_estatus.required' => 'El estatus es obligatorio.',
            'usr_estatus.in' => 'El estatus enviado no es válido.',
        ]);

        $registro = $this->usuarioService->cambiarEstatus($request, $usuario, $request->string('usr_estatus')->toString());

        return response()->json([
            'message' => 'Estatus actualizado correctamente.',
            'data' => ['usr_estatus' => $registro->usr_estatus],
        ]);
    }
}
