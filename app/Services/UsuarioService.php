<?php

namespace App\Services;

use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Usuario::query()
            ->with(['roles:rol_id,rol_nombre', 'sucursales:scl_id,scl_nombre'])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = $filtros['buscar'];
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('usr_usuario', 'like', "%{$buscar}%")
                        ->orWhere('usr_email', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('usr_nombre')
            ->get();
    }

    public function obtenerPorId(int $usuarioId): Usuario
    {
        return Usuario::query()->findOrFail($usuarioId);
    }

    public function crear(Request $request, array $datos): Usuario
    {
        return DB::transaction(function () use ($request, $datos): Usuario {
            $usuario = Usuario::query()->create([
                'usr_nombre' => $datos['usr_nombre'],
                'usr_usuario' => $datos['usr_usuario'],
                'usr_email' => Arr::get($datos, 'usr_email'),
                'usr_password' => Hash::make($datos['usr_password']),
                'usr_estatus' => $datos['usr_estatus'],
                'usr_created_by_usr_id' => optional($request->user())->usr_id,
                'usr_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarRoles($usuario->usr_id, $datos['roles']);
            $this->sincronizarSucursales($usuario->usr_id, $datos['sucursales'], Arr::get($datos, 'usc_scl_predeterminada'));

            $this->auditoriaService->registrarAccion(
                $request,
                'usuario.crear',
                'tbl_usuarios_usr',
                (string) $usuario->usr_id,
                [
                    'usr_usuario' => $usuario->usr_usuario,
                    'usr_estatus' => $usuario->usr_estatus,
                ]
            );

            return $usuario;
        });
    }

    public function actualizar(Request $request, int $usuarioId, array $datos): Usuario
    {
        return DB::transaction(function () use ($request, $usuarioId, $datos): Usuario {
            $usuario = Usuario::query()->findOrFail($usuarioId);

            $usuario->fill([
                'usr_nombre' => $datos['usr_nombre'],
                'usr_usuario' => $datos['usr_usuario'],
                'usr_email' => Arr::get($datos, 'usr_email'),
                'usr_estatus' => $datos['usr_estatus'],
                'usr_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            if (!empty($datos['usr_password'])) {
                $usuario->usr_password = Hash::make($datos['usr_password']);
            }

            $usuario->save();

            $this->sincronizarRoles($usuario->usr_id, $datos['roles']);
            $this->sincronizarSucursales($usuario->usr_id, $datos['sucursales'], Arr::get($datos, 'usc_scl_predeterminada'));

            $this->auditoriaService->registrarAccion(
                $request,
                'usuario.editar',
                'tbl_usuarios_usr',
                (string) $usuario->usr_id,
                [
                    'usr_usuario' => $usuario->usr_usuario,
                    'usr_estatus' => $usuario->usr_estatus,
                ]
            );

            return $usuario;
        });
    }

    public function cambiarEstatus(Request $request, int $usuarioId, string $estatus): Usuario
    {
        $usuario = Usuario::query()->findOrFail($usuarioId);
        $usuario->update([
            'usr_estatus' => $estatus,
            'usr_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'usuario.activar' : 'usuario.inactivar',
            'tbl_usuarios_usr',
            (string) $usuario->usr_id,
            ['usr_estatus' => $estatus]
        );

        return $usuario;
    }

    public function opcionesParaFormulario(): array
    {
        return [
            'roles' => \App\Models\Rol::query()
                ->where('rol_estatus', 'activo')
                ->orderBy('rol_nombre')
                ->get(['rol_id', 'rol_nombre']),
            'sucursales' => Sucursal::query()
                ->where('scl_estatus', 'activo')
                ->orderBy('scl_nombre')
                ->get(['scl_id', 'scl_nombre']),
        ];
    }

    private function sincronizarRoles(int $usuarioId, array $rolesIds): void
    {
        $rolesIds = array_values(array_unique(array_map('intval', $rolesIds)));

        UsuarioRol::query()
            ->where('url_usr_id', $usuarioId)
            ->where('url_deleted', false)
            ->whereNotIn('url_rol_id', $rolesIds)
            ->update([
                'url_deleted' => true,
                'url_deleted_at' => now(),
                'url_estatus' => 'inactivo',
                'url_updated_at' => now(),
            ]);

        foreach ($rolesIds as $rolId) {
            $registro = UsuarioRol::query()
                ->withoutGlobalScopes()
                ->where('url_usr_id', $usuarioId)
                ->where('url_rol_id', $rolId)
                ->first();

            if ($registro) {
                $registro->update([
                    'url_deleted' => false,
                    'url_deleted_at' => null,
                    'url_estatus' => 'activo',
                ]);
                continue;
            }

            UsuarioRol::query()->create([
                'url_usr_id' => $usuarioId,
                'url_rol_id' => $rolId,
                'url_estatus' => 'activo',
                'url_deleted' => false,
            ]);
        }
    }

    private function sincronizarSucursales(int $usuarioId, array $sucursalesIds, ?int $predeterminada): void
    {
        $sucursalesIds = array_values(array_unique(array_map('intval', $sucursalesIds)));
        $predeterminada = $predeterminada && in_array($predeterminada, $sucursalesIds, true)
            ? $predeterminada
            : $sucursalesIds[0];

        UsuarioSucursal::query()
            ->where('usc_usr_id', $usuarioId)
            ->where('usc_deleted', false)
            ->whereNotIn('usc_scl_id', $sucursalesIds)
            ->update([
                'usc_deleted' => true,
                'usc_deleted_at' => now(),
                'usc_estatus' => 'inactivo',
                'usc_updated_at' => now(),
            ]);

        foreach ($sucursalesIds as $sucursalId) {
            $registro = UsuarioSucursal::query()
                ->withoutGlobalScopes()
                ->where('usc_usr_id', $usuarioId)
                ->where('usc_scl_id', $sucursalId)
                ->first();

            $datos = [
                'usc_deleted' => false,
                'usc_deleted_at' => null,
                'usc_estatus' => 'activo',
                'usc_es_predeterminada' => $predeterminada === $sucursalId,
            ];

            if ($registro) {
                $registro->update($datos);
                continue;
            }

            UsuarioSucursal::query()->create(array_merge($datos, [
                'usc_usr_id' => $usuarioId,
                'usc_scl_id' => $sucursalId,
            ]));
        }

        UsuarioSucursal::query()
            ->where('usc_usr_id', $usuarioId)
            ->where('usc_deleted', false)
            ->where('usc_scl_id', '!=', $predeterminada)
            ->update(['usc_es_predeterminada' => false]);
    }
}
