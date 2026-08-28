<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use Illuminate\Database\Eloquent\Collection;
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
        $usuario = $this->autenticar($request, $usuarioIngresado, $password);

        if (!$usuario) {
            return false;
        }

        Auth::login($usuario);
        $sucursalPredeterminadaId = $this->sucursalPredeterminadaId($usuario);

        if ($sucursalPredeterminadaId) {
            $request->session()->put('sucursal_activa_id', $sucursalPredeterminadaId);
        }

        return true;
    }

    public function autenticar(Request $request, string $usuarioIngresado, string $password): ?Usuario
    {
        $usuario = Usuario::query()
            ->where('usr_usuario', $usuarioIngresado)
            ->first();

        if (!$usuario) {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'usuario_no_encontrado');

            return null;
        }

        if ($usuario->usr_estatus !== 'activo') {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'usuario_inactivo', $usuario->usr_id);

            return null;
        }

        if (!Hash::check($password, $usuario->usr_password)) {
            $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'fallido', 'contrasena_incorrecta', $usuario->usr_id);

            return null;
        }

        $this->auditoriaService->registrarAcceso($request, $usuarioIngresado, 'exitoso', null, $usuario->usr_id);

        return $usuario;
    }

    public function buscarUsuariosActivos(string $texto, int $limite = 10): Collection
    {
        $q = trim($texto);

        if (mb_strlen($q) < 2) {
            return new Collection();
        }

        return Usuario::query()
            ->select(['usr_usuario', 'usr_nombre'])
            ->where('usr_estatus', 'activo')
            ->where(function ($query) use ($q): void {
                $query->where('usr_usuario', 'like', "%{$q}%")
                    ->orWhere('usr_nombre', 'like', "%{$q}%");
            })
            ->orderBy('usr_usuario')
            ->limit(max(1, min($limite, 20)))
            ->get();
    }

    public function sucursalPredeterminadaId(Usuario $usuario): ?int
    {
        $sucursalId = UsuarioSucursal::query()
            ->where('usc_usr_id', $usuario->usr_id)
            ->where('usc_deleted', false)
            ->whereNull('usc_deleted_at')
            ->where('usc_estatus', 'activo')
            ->orderByDesc('usc_es_predeterminada')
            ->value('usc_scl_id');

        return $sucursalId ? (int) $sucursalId : null;
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
