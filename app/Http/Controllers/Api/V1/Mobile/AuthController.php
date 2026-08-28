<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function usuarios(Request $request, AuthService $authService): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $usuarios = $authService
            ->buscarUsuariosActivos((string) ($datos['q'] ?? ''))
            ->map(fn (Usuario $usuario): array => [
                'usuario' => (string) $usuario->usr_usuario,
                'nombre' => (string) $usuario->usr_nombre,
            ])
            ->values();

        return response()->json(['data' => $usuarios]);
    }

    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $usuario = $authService->autenticar(
            $request,
            $request->string('usuario')->trim()->toString(),
            $request->string('password')->toString(),
        );

        if (!$usuario) {
            return response()->json([
                'message' => 'Credenciales inválidas o usuario inactivo.',
                'errors' => [
                    'password' => ['Revisa el usuario y la contraseña.'],
                ],
            ], 422);
        }

        $usuario->tokens()->where('name', 'suriana-vendedor-android')->delete();
        $token = $usuario->createToken('suriana-vendedor-android', ['mobile:orders'])->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'usuario' => $this->mapUsuario($usuario, $authService),
            ],
        ]);
    }

    public function sesion(Request $request, AuthService $authService): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return response()->json([
            'data' => [
                'usuario' => $this->mapUsuario($usuario, $authService),
            ],
        ]);
    }

    public function logout(Request $request, AuditoriaService $auditoriaService): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        $auditoriaService->registrarAccion(
            $request,
            'seguridad.cerrar_sesion_mobile',
            'usuario',
            (string) $usuario->usr_id,
        );
        $usuario->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    private function mapUsuario(Usuario $usuario, AuthService $authService): array
    {
        $sucursales = $usuario->sucursales()
            ->select([
                'tbl_sucursales_scl.scl_id',
                'tbl_sucursales_scl.scl_nombre',
                'tbl_sucursales_scl.scl_clave',
            ])
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->get()
            ->map(fn ($sucursal): array => [
                'id' => (int) $sucursal->scl_id,
                'nombre' => (string) $sucursal->scl_nombre,
                'clave' => (string) $sucursal->scl_clave,
            ])
            ->values();

        return [
            'id' => (int) $usuario->usr_id,
            'usuario' => (string) $usuario->usr_usuario,
            'nombre' => (string) $usuario->usr_nombre,
            'sucursal_predeterminada_id' => $authService->sucursalPredeterminadaId($usuario),
            'sucursales' => $sucursales,
            'permisos' => [
                'ver_pedidos' => $usuario->tienePermiso('pedido_piso.ver'),
                'crear_pedidos' => $usuario->tienePermiso('pedido_piso.crear'),
                'cancelar_pedidos' => $usuario->tienePermiso('pedido_piso.eliminar')
                    || $usuario->tienePermiso('pedido_piso.crear'),
            ],
        ];
    }
}
