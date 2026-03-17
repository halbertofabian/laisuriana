<?php

namespace App\Services\Operacion\Comercial;

use App\Models\Atributo;
use App\Models\ProductoAtributo;
use App\Models\SkuValorAtributo;
use App\Models\ValorAtributo;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AtributoService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Atributo::query()
            ->withCount([
                'valores as valores_total' => fn ($query) => $query->where('vat_deleted', false)->whereNull('vat_deleted_at'),
            ])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('atr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('atr_clave', 'like', "%{$buscar}%")
                        ->orWhere('atr_tipo', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('atr_estatus', $filtros['estatus']);
            })
            ->orderBy('atr_nombre')
            ->get();
    }

    public function listarValores(array $filtros = [])
    {
        return ValorAtributo::query()
            ->with('atributo:atr_id,atr_nombre,atr_estatus')
            ->when(!empty($filtros['vat_atr_id']), function ($query) use ($filtros): void {
                $query->where('vat_atr_id', (int) $filtros['vat_atr_id']);
            })
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('vat_valor', 'like', "%{$buscar}%")
                        ->orWhere('vat_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('vat_estatus', $filtros['estatus']);
            })
            ->orderBy('vat_valor')
            ->get();
    }

    public function crear(Request $request, array $datos): Atributo
    {
        return DB::transaction(function () use ($request, $datos): Atributo {
            $claveIngresada = trim((string) Arr::get($datos, 'atr_clave', ''));

            $atributo = Atributo::query()->create([
                'atr_nombre' => $datos['atr_nombre'],
                'atr_clave' => $claveIngresada !== '' ? $claveIngresada : $this->generarClaveAtributo($datos['atr_nombre']),
                'atr_tipo' => $datos['atr_tipo'] ?? null,
                'atr_estatus' => $datos['atr_estatus'],
                'atr_created_by_usr_id' => optional($request->user())->usr_id,
                'atr_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.atributo.crear',
                'tbl_atributos_atr',
                (string) $atributo->atr_id,
                ['atr_clave' => $atributo->atr_clave]
            );

            return $atributo;
        });
    }

    public function actualizar(Request $request, int $id, array $datos): Atributo
    {
        return DB::transaction(function () use ($request, $id, $datos): Atributo {
            $atributo = Atributo::query()->findOrFail($id);
            $claveIngresada = trim((string) Arr::get($datos, 'atr_clave', ''));
            $claveActual = (string) $atributo->atr_clave;

            if ($datos['atr_estatus'] === 'inactivo') {
                $this->validarInactivacionAtributo($id);
            }

            $atributo->update([
                'atr_nombre' => $datos['atr_nombre'],
                'atr_clave' => $claveIngresada !== ''
                    ? $claveIngresada
                    : ($claveActual !== '' ? $claveActual : $this->generarClaveAtributo($datos['atr_nombre'], $id)),
                'atr_tipo' => $datos['atr_tipo'] ?? null,
                'atr_estatus' => $datos['atr_estatus'],
                'atr_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.atributo.editar',
                'tbl_atributos_atr',
                (string) $atributo->atr_id,
                ['atr_estatus' => $atributo->atr_estatus]
            );

            return $atributo;
        });
    }

    public function cambiarEstatus(Request $request, int $id, string $estatus): Atributo
    {
        $atributo = Atributo::query()->findOrFail($id);

        if ($estatus === 'inactivo') {
            $this->validarInactivacionAtributo($id);
        }

        $atributo->update([
            'atr_estatus' => $estatus,
            'atr_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.atributo.activar' : 'catalogo_comercial.atributo.inactivar',
            'tbl_atributos_atr',
            (string) $atributo->atr_id,
            ['atr_estatus' => $estatus]
        );

        return $atributo;
    }

    public function eliminar(Request $request, int $id): void
    {
        DB::transaction(function () use ($request, $id): void {
            $atributo = Atributo::query()->findOrFail($id);
            $this->validarInactivacionAtributo($id);

            $atributo->forceFill([
                'atr_estatus' => 'inactivo',
                'atr_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $atributo->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.atributo.eliminar',
                'tbl_atributos_atr',
                (string) $atributo->atr_id,
                ['atr_clave' => $atributo->atr_clave]
            );
        });
    }

    public function crearValor(Request $request, array $datos): ValorAtributo
    {
        return DB::transaction(function () use ($request, $datos): ValorAtributo {
            $this->validarAtributoActivo($datos['vat_atr_id']);
            $claveIngresada = trim((string) Arr::get($datos, 'vat_clave', ''));
            $this->validarValorUnico($datos['vat_atr_id'], $datos['vat_valor'], $claveIngresada !== '' ? $claveIngresada : null);

            $valor = ValorAtributo::query()->create([
                'vat_atr_id' => $datos['vat_atr_id'],
                'vat_valor' => $datos['vat_valor'],
                'vat_clave' => $claveIngresada !== '' ? $claveIngresada : $this->generarClaveValor($datos['vat_valor'], $datos['vat_atr_id']),
                'vat_estatus' => $datos['vat_estatus'],
                'vat_created_by_usr_id' => optional($request->user())->usr_id,
                'vat_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.valor_atributo.crear',
                'tbl_valores_atributo_vat',
                (string) $valor->vat_id,
                ['vat_atr_id' => $valor->vat_atr_id]
            );

            return $valor;
        });
    }

    public function actualizarValor(Request $request, int $id, array $datos): ValorAtributo
    {
        return DB::transaction(function () use ($request, $id, $datos): ValorAtributo {
            $valor = ValorAtributo::query()->findOrFail($id);
            $claveIngresada = trim((string) Arr::get($datos, 'vat_clave', ''));
            $claveActual = (string) $valor->vat_clave;

            if ($datos['vat_estatus'] === 'inactivo') {
                $this->validarInactivacionValor($id);
            }

            $this->validarAtributoActivo($datos['vat_atr_id']);
            $this->validarValorUnico($datos['vat_atr_id'], $datos['vat_valor'], $claveIngresada !== '' ? $claveIngresada : null, $id);

            $valor->update([
                'vat_atr_id' => $datos['vat_atr_id'],
                'vat_valor' => $datos['vat_valor'],
                'vat_clave' => $claveIngresada !== ''
                    ? $claveIngresada
                    : ($claveActual !== '' ? $claveActual : $this->generarClaveValor($datos['vat_valor'], $datos['vat_atr_id'], $id)),
                'vat_estatus' => $datos['vat_estatus'],
                'vat_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.valor_atributo.editar',
                'tbl_valores_atributo_vat',
                (string) $valor->vat_id,
                ['vat_estatus' => $valor->vat_estatus]
            );

            return $valor;
        });
    }

    public function cambiarEstatusValor(Request $request, int $id, string $estatus): ValorAtributo
    {
        $valor = ValorAtributo::query()->findOrFail($id);

        if ($estatus === 'inactivo') {
            $this->validarInactivacionValor($id);
        }

        $valor->update([
            'vat_estatus' => $estatus,
            'vat_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.valor_atributo.activar' : 'catalogo_comercial.valor_atributo.inactivar',
            'tbl_valores_atributo_vat',
            (string) $valor->vat_id,
            ['vat_estatus' => $estatus]
        );

        return $valor;
    }

    public function eliminarValor(Request $request, int $id): void
    {
        DB::transaction(function () use ($request, $id): void {
            $valor = ValorAtributo::query()->findOrFail($id);
            $this->validarInactivacionValor($id);

            $valor->forceFill([
                'vat_estatus' => 'inactivo',
                'vat_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $valor->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.valor_atributo.eliminar',
                'tbl_valores_atributo_vat',
                (string) $valor->vat_id,
                ['vat_clave' => $valor->vat_clave]
            );
        });
    }

    public function opcionesAtributosActivos()
    {
        return Atributo::query()
            ->where('atr_estatus', 'activo')
            ->orderBy('atr_nombre')
            ->get(['atr_id', 'atr_nombre']);
    }

    public function opcionesValoresActivos(?int $atributoId = null)
    {
        return ValorAtributo::query()
            ->with('atributo:atr_id,atr_nombre')
            ->when($atributoId, fn ($query) => $query->where('vat_atr_id', $atributoId))
            ->where('vat_estatus', 'activo')
            ->orderBy('vat_valor')
            ->get(['vat_id', 'vat_atr_id', 'vat_valor']);
    }

    private function validarAtributoActivo(int $atributoId): void
    {
        $activo = Atributo::query()
            ->where('atr_id', $atributoId)
            ->where('atr_estatus', 'activo')
            ->exists();

        if (!$activo) {
            throw ValidationException::withMessages([
                'vat_atr_id' => 'El atributo seleccionado no existe o está inactivo.',
            ]);
        }
    }

    private function validarValorUnico(int $atributoId, string $valor, ?string $clave, ?int $idIgnorar = null): void
    {
        $valorExiste = ValorAtributo::query()
            ->withDeleted()
            ->where('vat_atr_id', $atributoId)
            ->where('vat_valor', $valor)
            ->where('vat_deleted', false)
            ->when($idIgnorar, fn ($query) => $query->where('vat_id', '!=', $idIgnorar))
            ->exists();

        if ($valorExiste) {
            throw ValidationException::withMessages([
                'vat_valor' => 'Ya existe ese valor para el atributo seleccionado.',
            ]);
        }

        if ($clave) {
            $claveExiste = ValorAtributo::query()
                ->withDeleted()
                ->where('vat_atr_id', $atributoId)
                ->where('vat_clave', $clave)
                ->where('vat_deleted', false)
                ->when($idIgnorar, fn ($query) => $query->where('vat_id', '!=', $idIgnorar))
                ->exists();

            if ($claveExiste) {
                throw ValidationException::withMessages([
                    'vat_clave' => 'Ya existe esa clave para el atributo seleccionado.',
                ]);
            }
        }
    }

    private function validarInactivacionAtributo(int $atributoId): void
    {
        $enUsoProducto = ProductoAtributo::query()
            ->where('pat_atr_id', $atributoId)
            ->where('pat_deleted', false)
            ->whereNull('pat_deleted_at')
            ->exists();

        if ($enUsoProducto) {
            throw ValidationException::withMessages([
                'atr_estatus' => 'No puedes inactivar/eliminar el atributo porque está relacionado con productos.',
            ]);
        }
    }

    private function validarInactivacionValor(int $valorId): void
    {
        $enUsoSku = SkuValorAtributo::query()
            ->where('sva_vat_id', $valorId)
            ->where('sva_deleted', false)
            ->whereNull('sva_deleted_at')
            ->exists();

        if ($enUsoSku) {
            throw ValidationException::withMessages([
                'vat_estatus' => 'No puedes inactivar/eliminar el valor porque está relacionado con variantes SKU.',
            ]);
        }
    }

    private function generarClaveAtributo(string $nombre, ?int $idIgnorar = null): string
    {
        $base = 'ATR_' . (string) Str::of($nombre)->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '_')->trim('_')->upper();
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $i = 2;

        while (Atributo::query()->withDeleted()->where('atr_clave', $candidato)->when($idIgnorar, fn ($q) => $q->where('atr_id', '!=', $idIgnorar))->exists()) {
            $sufijo = '_' . $i;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $i++;
        }

        return $candidato;
    }

    private function generarClaveValor(string $valor, int $atributoId, ?int $idIgnorar = null): string
    {
        $base = 'VAT_' . $atributoId . '_' . (string) Str::of($valor)->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '_')->trim('_')->upper();
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $i = 2;

        while (ValorAtributo::query()->withDeleted()->where('vat_atr_id', $atributoId)->where('vat_clave', $candidato)->when($idIgnorar, fn ($q) => $q->where('vat_id', '!=', $idIgnorar))->exists()) {
            $sufijo = '_' . $i;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $i++;
        }

        return $candidato;
    }
}
