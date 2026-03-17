<?php

namespace App\Services;

use App\Models\Rol;
use App\Models\RolPermiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar()
    {
        return Rol::query()
            ->with('permisos:prm_id,prm_clave,prm_descripcion')
            ->orderBy('rol_nombre')
            ->get();
    }

    public function obtenerPorId(int $rolId): Rol
    {
        return Rol::query()->findOrFail($rolId);
    }

    public function crear(Request $request, array $datos): Rol
    {
        return DB::transaction(function () use ($request, $datos): Rol {
            $rol = Rol::query()->create([
                'rol_nombre' => $datos['rol_nombre'],
                'rol_descripcion' => $datos['rol_descripcion'] ?? null,
                'rol_estatus' => $datos['rol_estatus'],
                'rol_created_by_usr_id' => optional($request->user())->usr_id,
                'rol_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarPermisos($rol->rol_id, $datos['permisos']);

            $this->auditoriaService->registrarAccion($request, 'rol.crear', 'tbl_roles_rol', (string) $rol->rol_id, [
                'rol_nombre' => $rol->rol_nombre,
                'rol_estatus' => $rol->rol_estatus,
            ]);

            return $rol;
        });
    }

    public function actualizar(Request $request, int $rolId, array $datos): Rol
    {
        return DB::transaction(function () use ($request, $rolId, $datos): Rol {
            $rol = Rol::query()->findOrFail($rolId);
            $rol->update([
                'rol_nombre' => $datos['rol_nombre'],
                'rol_descripcion' => $datos['rol_descripcion'] ?? null,
                'rol_estatus' => $datos['rol_estatus'],
                'rol_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarPermisos($rol->rol_id, $datos['permisos']);

            $this->auditoriaService->registrarAccion($request, 'rol.editar', 'tbl_roles_rol', (string) $rol->rol_id, [
                'rol_nombre' => $rol->rol_nombre,
                'rol_estatus' => $rol->rol_estatus,
            ]);

            return $rol;
        });
    }

    public function cambiarEstatus(Request $request, int $rolId, string $estatus): Rol
    {
        $rol = Rol::query()->findOrFail($rolId);
        $rol->update([
            'rol_estatus' => $estatus,
            'rol_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'rol.activar' : 'rol.inactivar',
            'tbl_roles_rol',
            (string) $rol->rol_id,
            ['rol_estatus' => $estatus]
        );

        return $rol;
    }

    private function sincronizarPermisos(int $rolId, array $permisosIds): void
    {
        $permisosIds = array_values(array_unique(array_map('intval', $permisosIds)));

        RolPermiso::query()
            ->where('rpm_rol_id', $rolId)
            ->where('rpm_deleted', false)
            ->whereNotIn('rpm_prm_id', $permisosIds)
            ->update([
                'rpm_deleted' => true,
                'rpm_deleted_at' => now(),
                'rpm_estatus' => 'inactivo',
                'rpm_updated_at' => now(),
            ]);

        foreach ($permisosIds as $permisoId) {
            $registro = RolPermiso::query()
                ->withoutGlobalScopes()
                ->where('rpm_rol_id', $rolId)
                ->where('rpm_prm_id', $permisoId)
                ->first();

            if ($registro) {
                $registro->update([
                    'rpm_deleted' => false,
                    'rpm_deleted_at' => null,
                    'rpm_estatus' => 'activo',
                ]);

                continue;
            }

            RolPermiso::query()->create([
                'rpm_rol_id' => $rolId,
                'rpm_prm_id' => $permisoId,
                'rpm_estatus' => 'activo',
                'rpm_deleted' => false,
            ]);
        }
    }
}
