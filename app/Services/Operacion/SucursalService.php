<?php

namespace App\Services\Operacion;

use App\Models\Sucursal;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SucursalService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Sucursal::query()
            ->withCount([
                'almacenes as almacenes_total' => fn ($query) => $query->where('alm_deleted', false)->whereNull('alm_deleted_at'),
                'almacenes as almacenes_activos' => fn ($query) => $query
                    ->where('alm_deleted', false)
                    ->whereNull('alm_deleted_at')
                    ->where('alm_estatus', 'activo'),
            ])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('scl_nombre', 'like', "%{$buscar}%")
                        ->orWhere('scl_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('scl_estatus', $filtros['estatus']);
            })
            ->orderBy('scl_nombre')
            ->get();
    }

    public function opcionesActivas()
    {
        return Sucursal::query()
            ->where('scl_estatus', 'activo')
            ->orderBy('scl_nombre')
            ->get(['scl_id', 'scl_nombre']);
    }

    public function obtenerPorId(int $sucursalId): Sucursal
    {
        return Sucursal::query()->findOrFail($sucursalId);
    }

    public function crear(Request $request, array $datos): Sucursal
    {
        return DB::transaction(function () use ($request, $datos): Sucursal {
            $sucursal = Sucursal::query()->create([
                'scl_nombre' => $datos['scl_nombre'],
                'scl_clave' => $this->generarClaveInterna($datos['scl_nombre']),
                'scl_estatus' => $datos['scl_estatus'],
                'scl_created_by_usr_id' => optional($request->user())->usr_id,
                'scl_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'sucursal.crear',
                'tbl_sucursales_scl',
                (string) $sucursal->scl_id,
                [
                    'scl_clave' => $sucursal->scl_clave,
                    'scl_estatus' => $sucursal->scl_estatus,
                ]
            );

            return $sucursal;
        });
    }

    public function actualizar(Request $request, int $sucursalId, array $datos): Sucursal
    {
        return DB::transaction(function () use ($request, $sucursalId, $datos): Sucursal {
            $sucursal = Sucursal::query()->findOrFail($sucursalId);

            $sucursal->update([
                'scl_nombre' => $datos['scl_nombre'],
                'scl_estatus' => $datos['scl_estatus'],
                'scl_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            if ($datos['scl_estatus'] === 'inactivo') {
                $this->validarInactivacion($sucursal);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'sucursal.editar',
                'tbl_sucursales_scl',
                (string) $sucursal->scl_id,
                [
                    'scl_clave' => $sucursal->scl_clave,
                    'scl_estatus' => $sucursal->scl_estatus,
                ]
            );

            return $sucursal;
        });
    }

    public function cambiarEstatus(Request $request, int $sucursalId, string $estatus): Sucursal
    {
        $sucursal = Sucursal::query()->findOrFail($sucursalId);

        if ($estatus === 'inactivo') {
            $this->validarInactivacion($sucursal);
        }

        $sucursal->update([
            'scl_estatus' => $estatus,
            'scl_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'sucursal.activar' : 'sucursal.inactivar',
            'tbl_sucursales_scl',
            (string) $sucursal->scl_id,
            ['scl_estatus' => $estatus]
        );

        return $sucursal;
    }

    public function eliminar(Request $request, int $sucursalId): void
    {
        DB::transaction(function () use ($request, $sucursalId): void {
            $sucursal = Sucursal::query()->findOrFail($sucursalId);

            $tieneAlmacenes = $sucursal->almacenes()
                ->where('alm_deleted', false)
                ->whereNull('alm_deleted_at')
                ->exists();

            if ($tieneAlmacenes) {
                throw ValidationException::withMessages([
                    'sucursal' => 'No puedes eliminar la sucursal porque tiene almacenes relacionados.',
                ]);
            }

            $sucursal->forceFill([
                'scl_estatus' => 'inactivo',
                'scl_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $sucursal->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'sucursal.eliminar',
                'tbl_sucursales_scl',
                (string) $sucursal->scl_id,
                [
                    'scl_clave' => $sucursal->scl_clave,
                    'scl_estatus' => $sucursal->scl_estatus,
                ]
            );
        });
    }

    private function validarInactivacion(Sucursal $sucursal): void
    {
        $activos = $sucursal->almacenes()
            ->where('alm_deleted', false)
            ->whereNull('alm_deleted_at')
            ->where('alm_estatus', 'activo')
            ->count();

        if ($activos > 0) {
            throw ValidationException::withMessages([
                'scl_estatus' => 'No puedes inactivar la sucursal porque tiene almacenes activos.',
            ]);
        }
    }

    private function generarClaveInterna(string $nombre): string
    {
        $limpio = (string) Str::of($nombre)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper();

        $base = 'SCL_' . ($limpio !== '' ? $limpio : 'SUCURSAL');
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while (Sucursal::query()->withoutGlobalScopes()->where('scl_clave', $candidato)->exists()) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }
}
