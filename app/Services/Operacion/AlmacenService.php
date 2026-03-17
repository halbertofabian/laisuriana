<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AlmacenService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Almacen::query()
            ->with(['sucursal:scl_id,scl_nombre,scl_clave', 'tipo:tal_id,tal_nombre,tal_clave'])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('alm_nombre', 'like', "%{$buscar}%")
                        ->orWhere('alm_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('alm_estatus', $filtros['estatus']);
            })
            ->when(!empty($filtros['alm_scl_id']), function ($query) use ($filtros): void {
                $query->where('alm_scl_id', (int) $filtros['alm_scl_id']);
            })
            ->when(!empty($filtros['alm_tal_id']), function ($query) use ($filtros): void {
                $query->where('alm_tal_id', (int) $filtros['alm_tal_id']);
            })
            ->orderBy('alm_scl_id')
            ->orderBy('alm_nombre')
            ->get();
    }

    public function obtenerPorId(int $almacenId): Almacen
    {
        return Almacen::query()->findOrFail($almacenId);
    }

    public function crear(Request $request, array $datos): Almacen
    {
        return DB::transaction(function () use ($request, $datos): Almacen {
            $this->validarReferenciasActivas((int) $datos['alm_scl_id'], (int) $datos['alm_tal_id']);

            $almacen = Almacen::query()->create([
                'alm_scl_id' => $datos['alm_scl_id'],
                'alm_tal_id' => $datos['alm_tal_id'],
                'alm_nombre' => $datos['alm_nombre'],
                'alm_clave' => $this->generarClaveInterna((int) $datos['alm_scl_id'], (string) $datos['alm_nombre']),
                'alm_estatus' => $datos['alm_estatus'],
                'alm_created_by_usr_id' => optional($request->user())->usr_id,
                'alm_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'almacen.crear',
                'tbl_almacenes_alm',
                (string) $almacen->alm_id,
                [
                    'alm_scl_id' => $almacen->alm_scl_id,
                    'alm_tal_id' => $almacen->alm_tal_id,
                    'alm_clave' => $almacen->alm_clave,
                    'alm_estatus' => $almacen->alm_estatus,
                ]
            );

            return $almacen;
        });
    }

    public function actualizar(Request $request, int $almacenId, array $datos): Almacen
    {
        return DB::transaction(function () use ($request, $almacenId, $datos): Almacen {
            $almacen = Almacen::query()->findOrFail($almacenId);
            $this->validarReferenciasActivas((int) $datos['alm_scl_id'], (int) $datos['alm_tal_id']);

            $almacen->update([
                'alm_scl_id' => $datos['alm_scl_id'],
                'alm_tal_id' => $datos['alm_tal_id'],
                'alm_nombre' => $datos['alm_nombre'],
                'alm_estatus' => $datos['alm_estatus'],
                'alm_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'almacen.editar',
                'tbl_almacenes_alm',
                (string) $almacen->alm_id,
                [
                    'alm_scl_id' => $almacen->alm_scl_id,
                    'alm_tal_id' => $almacen->alm_tal_id,
                    'alm_clave' => $almacen->alm_clave,
                    'alm_estatus' => $almacen->alm_estatus,
                ]
            );

            return $almacen;
        });
    }

    public function cambiarEstatus(Request $request, int $almacenId, string $estatus): Almacen
    {
        $almacen = Almacen::query()->findOrFail($almacenId);

        if ($estatus === 'activo') {
            $this->validarReferenciasActivas($almacen->alm_scl_id, $almacen->alm_tal_id);
        }

        $almacen->update([
            'alm_estatus' => $estatus,
            'alm_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'almacen.activar' : 'almacen.inactivar',
            'tbl_almacenes_alm',
            (string) $almacen->alm_id,
            ['alm_estatus' => $estatus]
        );

        return $almacen;
    }

    public function eliminar(Request $request, int $almacenId): void
    {
        $almacen = Almacen::query()->findOrFail($almacenId);

        $almacen->forceFill([
            'alm_estatus' => 'inactivo',
            'alm_updated_by_usr_id' => optional($request->user())->usr_id,
        ])->save();

        $almacen->marcarComoEliminado();

        $this->auditoriaService->registrarAccion(
            $request,
            'almacen.eliminar',
            'tbl_almacenes_alm',
            (string) $almacen->alm_id,
            [
                'alm_clave' => $almacen->alm_clave,
                'alm_estatus' => $almacen->alm_estatus,
            ]
        );
    }

    private function validarReferenciasActivas(int $sucursalId, int $tipoId): void
    {
        $sucursalActiva = Sucursal::query()
            ->where('scl_id', $sucursalId)
            ->where('scl_estatus', 'activo')
            ->exists();

        if (!$sucursalActiva) {
            throw ValidationException::withMessages([
                'alm_scl_id' => 'La sucursal seleccionada no está activa o no existe.',
            ]);
        }

        $tipoActivo = TipoAlmacen::query()
            ->where('tal_id', $tipoId)
            ->where('tal_estatus', 'activo')
            ->exists();

        if (!$tipoActivo) {
            throw ValidationException::withMessages([
                'alm_tal_id' => 'El tipo de almacén seleccionado no está activo o no existe.',
            ]);
        }
    }

    private function generarClaveInterna(int $sucursalId, string $nombre): string
    {
        $limpio = (string) Str::of($nombre)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper();

        $base = 'ALM_' . $sucursalId . '_' . ($limpio !== '' ? $limpio : 'ALMACEN');
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while (
            Almacen::query()
                ->where('alm_scl_id', $sucursalId)
                ->where('alm_clave', $candidato)
                ->where('alm_deleted', false)
                ->whereNull('alm_deleted_at')
                ->exists()
        ) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }
}
