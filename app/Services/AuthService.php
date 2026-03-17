<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function login(Request $request, string $usuarioIngresado, string $password): bool
    {
        $usuario = Usuario::query()
            ->where('usr_usuario', $usuarioIngresado)
            ->first();

        if (!$usuario) {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'usuario_no_encontrado');

            return false;
        }

        if ($usuario->usr_estatus !== 'activo') {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'usuario_inactivo', $usuario->usr_id);

            return false;
        }

        if (!Hash::check($password, $usuario->usr_password)) {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'contrasena_incorrecta', $usuario->usr_id);

            return false;
        }

        Auth::login($usuario);
        $sucursalPredeterminada = UsuarioSucursal::query()
            ->where('usc_usr_id', $usuario->usr_id)
            ->where('usc_deleted', false)
            ->whereNull('usc_deleted_at')
            ->where('usc_estatus', 'activo')
            ->orderByDesc('usc_es_predeterminada')
            ->first();

        if ($sucursalPredeterminada) {
            $request->session()->put('sucursal_activa_id', $sucursalPredeterminada->usc_scl_id);
        }

        $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'exitoso', null, $usuario->usr_id);

        return true;
    }

    public function logout(Request $request): void
    {
        $usuario = $request->user();

        if ($usuario) {
            $this->auditoriaService->registrarAccion($request, 'seguridad.cerrar_sesion', 'usuario', (string) $usuario->usr_id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
