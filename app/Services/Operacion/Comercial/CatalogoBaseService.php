<?php

namespace App\Services\Operacion\Comercial;

use App\Models\Categoria;
use App\Models\Concepto;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\UnidadMedida;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogoBaseService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(string $tipo, array $filtros = [])
    {
        $config = $this->config($tipo);

        $query = $config['model']::query()
            ->when($tipo === 'categorias', fn ($q) => $q->with('linea:lna_id,lna_nombre'))
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros, $config): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar, $config): void {
                    $subQuery->where($config['nombre'], 'like', "%{$buscar}%")
                        ->orWhere($config['clave'], 'like', "%{$buscar}%");

                    if (!empty($config['codigo'])) {
                        $subQuery->orWhere($config['codigo'], 'like', "%{$buscar}%");
                    }
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros, $config): void {
                $query->where($config['estatus'], $filtros['estatus']);
            });

        if ($tipo === 'unidades') {
            $query->orderByDesc('umd_es_predeterminada');
        }

        return $query
            ->orderBy($config['nombre'])
            ->get();
    }

    public function opcionesActivas(string $tipo)
    {
        $config = $this->config($tipo);

        $columns = [$config['id'], $config['nombre']];

        if ($tipo === 'unidades') {
            $columns[] = 'umd_codigo';
            $columns[] = 'umd_tipo_cantidad';
            $columns[] = 'umd_es_predeterminada';
        }

        $query = $config['model']::query()
            ->where($config['estatus'], 'activo')
            ->when($tipo === 'unidades', fn ($builder) => $builder->orderByDesc('umd_es_predeterminada'))
            ->orderBy($config['nombre']);

        return $query->get($columns);
    }

    public function obtenerPorId(string $tipo, int $id): Model
    {
        $config = $this->config($tipo);

        $query = $config['model']::query();

        if ($tipo === 'categorias') {
            $query->with('linea:lna_id,lna_nombre');
        }

        return $query->findOrFail($id);
    }

    public function crear(string $tipo, Request $request, array $datos): Model
    {
        $config = $this->config($tipo);

        return DB::transaction(function () use ($tipo, $request, $datos, $config): Model {
            $claveIngresada = trim((string) Arr::get($datos, 'clave', ''));
            $payload = [
                $config['nombre'] => $datos['nombre'],
                $config['clave'] => $claveIngresada !== ''
                    ? $claveIngresada
                    : $this->generarClaveInterna($config['prefijo'], $datos['nombre'], $config['model'], $config['clave']),
                $config['estatus'] => $datos['estatus'],
                $config['created_by'] => optional($request->user())->usr_id,
                $config['updated_by'] => optional($request->user())->usr_id,
            ];

            if (!empty($config['codigo'])) {
                $payload[$config['codigo']] = Arr::get($datos, 'codigo');
            }

            if ($tipo === 'categorias') {
                $payload['ctg_lna_id'] = Arr::get($datos, 'lna_id') ?: null;
            }

            if ($tipo === 'unidades') {
                $payload['umd_tipo_cantidad'] = Arr::get($datos, 'tipo_cantidad', 'entero');
                $payload['umd_es_predeterminada'] = $datos['estatus'] === 'activo'
                    && filter_var(Arr::get($datos, 'es_predeterminada', false), FILTER_VALIDATE_BOOLEAN);
            }

            $registro = $config['model']::query()->create($payload);

            if ($tipo === 'unidades' && $registro->umd_es_predeterminada) {
                $this->sincronizarUnidadPredeterminada($registro->umd_id);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                "catalogo_comercial.{$tipo}.crear",
                $config['table'],
                (string) $registro->{$config['id']},
                [
                    $config['clave'] => $registro->{$config['clave']},
                    $config['estatus'] => $registro->{$config['estatus']},
                    ...($tipo === 'unidades'
                        ? [
                            'umd_tipo_cantidad' => $registro->umd_tipo_cantidad,
                            'umd_es_predeterminada' => $registro->umd_es_predeterminada,
                        ]
                        : []),
                ]
            );

            return $registro;
        });
    }

    public function actualizar(string $tipo, Request $request, int $id, array $datos): Model
    {
        $config = $this->config($tipo);

        return DB::transaction(function () use ($tipo, $request, $id, $datos, $config): Model {
            $registro = $config['model']::query()->findOrFail($id);
            $claveIngresada = trim((string) Arr::get($datos, 'clave', ''));
            $claveActual = (string) $registro->{$config['clave']};
            $payload = [
                $config['nombre'] => $datos['nombre'],
                $config['clave'] => $claveIngresada !== ''
                    ? $claveIngresada
                    : ($claveActual !== ''
                        ? $claveActual
                        : $this->generarClaveInterna($config['prefijo'], $datos['nombre'], $config['model'], $config['clave'], $id, $config['id'])),
                $config['estatus'] => $datos['estatus'],
                $config['updated_by'] => optional($request->user())->usr_id,
            ];

            if (!empty($config['codigo'])) {
                $payload[$config['codigo']] = Arr::get($datos, 'codigo');
            }

            if ($tipo === 'categorias') {
                $payload['ctg_lna_id'] = Arr::get($datos, 'lna_id') ?: null;
            }

            if ($tipo === 'unidades') {
                $payload['umd_tipo_cantidad'] = Arr::get($datos, 'tipo_cantidad', $registro->umd_tipo_cantidad ?: 'entero');
                $payload['umd_es_predeterminada'] = $datos['estatus'] === 'activo'
                    && filter_var(Arr::get($datos, 'es_predeterminada', false), FILTER_VALIDATE_BOOLEAN);
            }

            $registro->update($payload);

            if ($tipo === 'unidades') {
                if ($registro->umd_es_predeterminada) {
                    $this->sincronizarUnidadPredeterminada($registro->umd_id);
                } elseif ($registro->umd_estatus !== 'activo') {
                    $registro->update(['umd_es_predeterminada' => false]);
                }
            }

            $this->auditoriaService->registrarAccion(
                $request,
                "catalogo_comercial.{$tipo}.editar",
                $config['table'],
                (string) $registro->{$config['id']},
                [
                    $config['clave'] => $registro->{$config['clave']},
                    $config['estatus'] => $registro->{$config['estatus']},
                    ...($tipo === 'unidades'
                        ? [
                            'umd_tipo_cantidad' => $registro->umd_tipo_cantidad,
                            'umd_es_predeterminada' => $registro->umd_es_predeterminada,
                        ]
                        : []),
                ]
            );

            return $registro;
        });
    }

    public function cambiarEstatus(string $tipo, Request $request, int $id, string $estatus): Model
    {
        $config = $this->config($tipo);
        $registro = $config['model']::query()->findOrFail($id);

        if ($estatus === 'inactivo') {
            $this->validarUsoEnProductos($tipo, $config['id'], $id);
        }

        $registro->update([
            $config['estatus'] => $estatus,
            $config['updated_by'] => optional($request->user())->usr_id,
            ...($tipo === 'unidades' && $estatus !== 'activo' ? ['umd_es_predeterminada' => false] : []),
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? "catalogo_comercial.{$tipo}.activar" : "catalogo_comercial.{$tipo}.inactivar",
            $config['table'],
            (string) $registro->{$config['id']},
            [
                $config['estatus'] => $estatus,
                ...($tipo === 'unidades' ? ['umd_es_predeterminada' => $registro->umd_es_predeterminada] : []),
            ]
        );

        return $registro;
    }

    public function eliminar(string $tipo, Request $request, int $id): void
    {
        $config = $this->config($tipo);

        DB::transaction(function () use ($tipo, $request, $id, $config): void {
            $registro = $config['model']::query()->findOrFail($id);
            $this->validarUsoEnProductos($tipo, $config['id'], $id);

            $registro->forceFill([
                $config['estatus'] => 'inactivo',
                $config['updated_by'] => optional($request->user())->usr_id,
                ...($tipo === 'unidades' ? ['umd_es_predeterminada' => false] : []),
            ])->save();

            $registro->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                "catalogo_comercial.{$tipo}.eliminar",
                $config['table'],
                (string) $registro->{$config['id']},
                [$config['clave'] => $registro->{$config['clave']}]
            );
        });
    }

    private function validarUsoEnProductos(string $tipo, string $idColumn, int $id): void
    {
        $columnaProducto = match ($tipo) {
            'marcas' => 'prd_mrc_id',
            'lineas' => 'prd_lna_id',
            'categorias' => 'prd_ctg_id',
            'unidades' => 'prd_umd_id',
            default => null,
        };

        if (!$columnaProducto) {
            return;
        }

        $enUso = Producto::query()
            ->where($columnaProducto, $id)
            ->where('prd_deleted', false)
            ->whereNull('prd_deleted_at')
            ->exists();

        if ($enUso) {
            throw ValidationException::withMessages([
                $idColumn => 'No se puede inactivar o eliminar porque tiene productos relacionados.',
            ]);
        }
    }

    private function generarClaveInterna(
        string $prefijo,
        string $nombre,
        string $modelClass,
        string $claveColumn,
        ?int $idIgnorar = null,
        ?string $idColumn = null
    ): string {
        $limpio = (string) Str::of($nombre)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper();

        $base = Str::substr($prefijo . '_' . ($limpio !== '' ? $limpio : 'REGISTRO'), 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while ($this->claveExiste($modelClass, $claveColumn, $candidato, $idIgnorar, $idColumn)) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function claveExiste(string $modelClass, string $claveColumn, string $clave, ?int $idIgnorar, ?string $idColumn): bool
    {
        $query = $modelClass::query()->withoutGlobalScopes()->where($claveColumn, $clave);

        if ($idIgnorar && $idColumn) {
            $query->where($idColumn, '!=', $idIgnorar);
        }

        return $query->exists();
    }

    private function config(string $tipo): array
    {
        return match ($tipo) {
            'marcas' => [
                'model' => Marca::class,
                'table' => 'tbl_marcas_mrc',
                'id' => 'mrc_id',
                'nombre' => 'mrc_nombre',
                'clave' => 'mrc_clave',
                'estatus' => 'mrc_estatus',
                'created_by' => 'mrc_created_by_usr_id',
                'updated_by' => 'mrc_updated_by_usr_id',
                'codigo' => null,
                'prefijo' => 'MRC',
            ],
            'lineas' => [
                'model' => Linea::class,
                'table' => 'tbl_lineas_lna',
                'id' => 'lna_id',
                'nombre' => 'lna_nombre',
                'clave' => 'lna_clave',
                'estatus' => 'lna_estatus',
                'created_by' => 'lna_created_by_usr_id',
                'updated_by' => 'lna_updated_by_usr_id',
                'codigo' => null,
                'prefijo' => 'LNA',
            ],
            'categorias' => [
                'model' => Categoria::class,
                'table' => 'tbl_categorias_ctg',
                'id' => 'ctg_id',
                'nombre' => 'ctg_nombre',
                'clave' => 'ctg_clave',
                'estatus' => 'ctg_estatus',
                'created_by' => 'ctg_created_by_usr_id',
                'updated_by' => 'ctg_updated_by_usr_id',
                'codigo' => null,
                'prefijo' => 'CTG',
            ],
            'unidades' => [
                'model' => UnidadMedida::class,
                'table' => 'tbl_unidades_medida_umd',
                'id' => 'umd_id',
                'nombre' => 'umd_nombre',
                'clave' => 'umd_clave',
                'estatus' => 'umd_estatus',
                'created_by' => 'umd_created_by_usr_id',
                'updated_by' => 'umd_updated_by_usr_id',
                'codigo' => 'umd_codigo',
                'prefijo' => 'UMD',
            ],
            'conceptos' => [
                'model' => Concepto::class,
                'table' => 'tbl_conceptos_cpt',
                'id' => 'cpt_id',
                'nombre' => 'cpt_nombre',
                'clave' => 'cpt_clave',
                'estatus' => 'cpt_estatus',
                'created_by' => 'cpt_created_by_usr_id',
                'updated_by' => 'cpt_updated_by_usr_id',
                'codigo' => null,
                'prefijo' => 'CPT',
            ],
            'motivos' => [
                'model' => Motivo::class,
                'table' => 'tbl_motivos_mtv',
                'id' => 'mtv_id',
                'nombre' => 'mtv_nombre',
                'clave' => 'mtv_clave',
                'estatus' => 'mtv_estatus',
                'created_by' => 'mtv_created_by_usr_id',
                'updated_by' => 'mtv_updated_by_usr_id',
                'codigo' => null,
                'prefijo' => 'MTV',
            ],
            default => throw new \InvalidArgumentException('Tipo de catálogo no soportado.'),
        };
    }

    private function sincronizarUnidadPredeterminada(int $unidadId): void
    {
        UnidadMedida::query()
            ->where('umd_id', '!=', $unidadId)
            ->where('umd_deleted', false)
            ->whereNull('umd_deleted_at')
            ->where('umd_es_predeterminada', true)
            ->update([
                'umd_es_predeterminada' => false,
                'umd_updated_at' => now(),
            ]);
    }
}
