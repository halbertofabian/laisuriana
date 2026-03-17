<?php

namespace App\Services\Operacion;

use App\Models\TipoAlmacen;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TipoAlmacenService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return TipoAlmacen::query()
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
                    $subQuery->where('tal_nombre', 'like', "%{$buscar}%")
                        ->orWhere('tal_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('tal_estatus', $filtros['estatus']);
            })
            ->orderBy('tal_nombre')
            ->get();
    }

    public function opcionesActivas()
    {
        return TipoAlmacen::query()
            ->where('tal_estatus', 'activo')
            ->orderBy('tal_nombre')
            ->get(['tal_id', 'tal_nombre']);
    }

    public function obtenerPorId(int $tipoAlmacenId): TipoAlmacen
    {
        return TipoAlmacen::query()->findOrFail($tipoAlmacenId);
    }

    public function crear(Request $request, array $datos): TipoAlmacen
    {
        return DB::transaction(function () use ($request, $datos): TipoAlmacen {
            $tipo = TipoAlmacen::query()->create([
                'tal_nombre' => $datos['tal_nombre'],
                'tal_clave' => $this->generarClaveInterna($datos['tal_nombre']),
                'tal_descripcion' => $datos['tal_descripcion'] ?? null,
                'tal_estatus' => $datos['tal_estatus'],
                'tal_created_by_usr_id' => optional($request->user())->usr_id,
                'tal_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'tipo_almacen.crear',
                'tbl_tipos_almacen_tal',
                (string) $tipo->tal_id,
                [
                    'tal_clave' => $tipo->tal_clave,
                    'tal_estatus' => $tipo->tal_estatus,
                ]
            );

            return $tipo;
        });
    }

    public function actualizar(Request $request, int $tipoAlmacenId, array $datos): TipoAlmacen
    {
        return DB::transaction(function () use ($request, $tipoAlmacenId, $datos): TipoAlmacen {
            $tipo = TipoAlmacen::query()->findOrFail($tipoAlmacenId);

            if ($datos['tal_estatus'] === 'inactivo') {
                $this->validarInactivacion($tipo);
            }

            $tipo->update([
                'tal_nombre' => $datos['tal_nombre'],
                'tal_descripcion' => $datos['tal_descripcion'] ?? null,
                'tal_estatus' => $datos['tal_estatus'],
                'tal_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'tipo_almacen.editar',
                'tbl_tipos_almacen_tal',
                (string) $tipo->tal_id,
                [
                    'tal_clave' => $tipo->tal_clave,
                    'tal_estatus' => $tipo->tal_estatus,
                ]
            );

            return $tipo;
        });
    }

    public function cambiarEstatus(Request $request, int $tipoAlmacenId, string $estatus): TipoAlmacen
    {
        $tipo = TipoAlmacen::query()->findOrFail($tipoAlmacenId);

        if ($estatus === 'inactivo') {
            $this->validarInactivacion($tipo);
        }

        $tipo->update([
            'tal_estatus' => $estatus,
            'tal_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'tipo_almacen.activar' : 'tipo_almacen.inactivar',
            'tbl_tipos_almacen_tal',
            (string) $tipo->tal_id,
            ['tal_estatus' => $estatus]
        );

        return $tipo;
    }

    public function eliminar(Request $request, int $tipoAlmacenId): void
    {
        DB::transaction(function () use ($request, $tipoAlmacenId): void {
            $tipo = TipoAlmacen::query()->findOrFail($tipoAlmacenId);

            $tieneAlmacenes = $tipo->almacenes()
                ->where('alm_deleted', false)
                ->whereNull('alm_deleted_at')
                ->exists();

            if ($tieneAlmacenes) {
                throw ValidationException::withMessages([
                    'tipo_almacen' => 'No puedes eliminar el tipo de almacén porque tiene almacenes relacionados.',
                ]);
            }

            $tipo->forceFill([
                'tal_estatus' => 'inactivo',
                'tal_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $tipo->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'tipo_almacen.eliminar',
                'tbl_tipos_almacen_tal',
                (string) $tipo->tal_id,
                [
                    'tal_clave' => $tipo->tal_clave,
                    'tal_estatus' => $tipo->tal_estatus,
                ]
            );
        });
    }

    private function validarInactivacion(TipoAlmacen $tipo): void
    {
        $activos = $tipo->almacenes()
            ->where('alm_deleted', false)
            ->whereNull('alm_deleted_at')
            ->where('alm_estatus', 'activo')
            ->count();

        if ($activos > 0) {
            throw ValidationException::withMessages([
                'tal_estatus' => 'No puedes inactivar el tipo de almacén porque tiene almacenes activos.',
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

        $base = 'TAL_' . ($limpio !== '' ? $limpio : 'TIPO');
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while (TipoAlmacen::query()->withoutGlobalScopes()->where('tal_clave', $candidato)->exists()) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }
}
