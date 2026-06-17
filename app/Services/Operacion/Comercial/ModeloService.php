<?php

namespace App\Services\Operacion\Comercial;

use App\Models\ModeloProducto;
use App\Models\Producto;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModeloService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = []): Collection
    {
        return ModeloProducto::query()
            ->with('marcas:mrc_id,mrc_nombre')
            ->when(!empty($filtros['buscar']), function ($q) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('mdl_nombre', 'like', "%{$buscar}%")
                        ->orWhere('mdl_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), fn ($q) => $q->where('mdl_estatus', $filtros['estatus']))
            ->orderBy('mdl_nombre')
            ->get();
    }

    public function opcionesActivas(): Collection
    {
        return ModeloProducto::query()
            ->where('mdl_estatus', 'activo')
            ->with('marcas:mrc_id,mrc_nombre')
            ->orderBy('mdl_nombre')
            ->get(['mdl_id', 'mdl_nombre', 'mdl_clave']);
    }

    public function modelosPorMarca(int $marcaId): Collection
    {
        return ModeloProducto::query()
            ->where('mdl_estatus', 'activo')
            ->whereHas('marcas', fn ($q) => $q->where('tbl_marcas_mrc.mrc_id', $marcaId))
            ->orderBy('mdl_nombre')
            ->get(['mdl_id', 'mdl_nombre', 'mdl_clave']);
    }

    public function obtenerPorId(int $id): ModeloProducto
    {
        return ModeloProducto::query()
            ->with('marcas:mrc_id,mrc_nombre')
            ->findOrFail($id);
    }

    public function crear(Request $request, array $datos): ModeloProducto
    {
        try {
            return DB::transaction(function () use ($request, $datos): ModeloProducto {
                $clave = trim((string) ($datos['clave'] ?? ''));
                if ($clave === '') {
                    $clave = $this->generarClave($datos['nombre']);
                }

                $modelo = ModeloProducto::query()->create([
                    'mdl_nombre'           => $datos['nombre'],
                    'mdl_clave'            => $clave,
                    'mdl_estatus'          => $datos['estatus'],
                    'mdl_created_by_usr_id' => optional($request->user())->usr_id,
                    'mdl_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);

                $modelo->marcas()->sync($datos['marca_ids'] ?? []);

                $this->auditoriaService->registrarAccion(
                    $request,
                    'catalogo_comercial.modelos.crear',
                    'tbl_modelos_mdl',
                    (string) $modelo->mdl_id,
                    ['mdl_clave' => $modelo->mdl_clave, 'mdl_estatus' => $modelo->mdl_estatus]
                );

                return $modelo;
            });
        } catch (QueryException $exception) {
            $this->throwIfDuplicateModelName($exception);

            throw $exception;
        }
    }

    public function actualizar(Request $request, int $id, array $datos): ModeloProducto
    {
        try {
            return DB::transaction(function () use ($request, $id, $datos): ModeloProducto {
                $modelo = ModeloProducto::query()->findOrFail($id);
                $clave = trim((string) ($datos['clave'] ?? ''));
                if ($clave === '') {
                    $clave = $modelo->mdl_clave ?: $this->generarClave($datos['nombre']);
                }

                $modelo->update([
                    'mdl_nombre'           => $datos['nombre'],
                    'mdl_clave'            => $clave,
                    'mdl_estatus'          => $datos['estatus'],
                    'mdl_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);

                $modelo->marcas()->sync($datos['marca_ids'] ?? []);

                $this->auditoriaService->registrarAccion(
                    $request,
                    'catalogo_comercial.modelos.editar',
                    'tbl_modelos_mdl',
                    (string) $modelo->mdl_id,
                    ['mdl_clave' => $modelo->mdl_clave, 'mdl_estatus' => $modelo->mdl_estatus]
                );

                return $modelo;
            });
        } catch (QueryException $exception) {
            $this->throwIfDuplicateModelName($exception);

            throw $exception;
        }
    }

    public function cambiarEstatus(Request $request, int $id, string $estatus): ModeloProducto
    {
        $modelo = ModeloProducto::query()->findOrFail($id);
        $modelo->update([
            'mdl_estatus'          => $estatus,
            'mdl_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.modelos.activar' : 'catalogo_comercial.modelos.inactivar',
            'tbl_modelos_mdl',
            (string) $modelo->mdl_id,
            ['mdl_estatus' => $estatus]
        );

        return $modelo;
    }

    public function eliminar(Request $request, int $id): void
    {
        DB::transaction(function () use ($request, $id): void {
            $modelo = ModeloProducto::query()->findOrFail($id);

            $enUso = Producto::query()
                ->where('prd_mdl_id', $id)
                ->where('prd_deleted', false)
                ->whereNull('prd_deleted_at')
                ->exists();

            if ($enUso) {
                throw ValidationException::withMessages([
                    'mdl_id' => 'No se puede eliminar porque tiene productos relacionados.',
                ]);
            }

            $modelo->forceFill([
                'mdl_estatus'          => 'inactivo',
                'mdl_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $modelo->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.modelos.eliminar',
                'tbl_modelos_mdl',
                (string) $modelo->mdl_id,
                ['mdl_clave' => $modelo->mdl_clave]
            );
        });
    }

    private function generarClave(string $nombre): string
    {
        $base = (string) Str::of($nombre)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper()
            ->substr(0, 36);

        $candidato = 'MDL_' . ($base !== '' ? $base : 'MODELO');
        $candidato = Str::substr($candidato, 0, 40);
        $consecutivo = 2;

        while (ModeloProducto::query()->withoutGlobalScopes()->where('mdl_clave', $candidato)->exists()) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr('MDL_' . $base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function throwIfDuplicateModelName(QueryException $exception): void
    {
        $duplicateEntry = (int) ($exception->errorInfo[1] ?? 0) === 1062;
        $sqlMessage = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

        if ($duplicateEntry && str_contains($sqlMessage, 'uk_modelo_nombre')) {
            throw ValidationException::withMessages([
                'nombre' => 'El nombre del modelo ya existe.',
            ]);
        }
    }
}
