<?php

namespace App\Services\Operacion;

use App\Models\Caja;
use App\Models\CajaUsuario;
use App\Models\Almacen;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CajaService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Caja::query()
            ->with(['sucursal:scl_id,scl_nombre', 'almacen:alm_id,alm_nombre', 'usuarios:usr_id,usr_nombre,usr_usuario'])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('caj_nombre', 'like', "%{$buscar}%")
                        ->orWhere('caj_clave', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('caj_estatus', $filtros['estatus']);
            })
            ->when(!empty($filtros['caj_scl_id']), function ($query) use ($filtros): void {
                $query->where('caj_scl_id', (int) $filtros['caj_scl_id']);
            })
            ->orderBy('caj_scl_id')
            ->orderBy('caj_nombre')
            ->get();
    }

    public function obtenerPorId(int $cajaId): Caja
    {
        return Caja::query()->with(['almacen:alm_id,alm_nombre', 'usuarios:usr_id,usr_nombre,usr_usuario'])->findOrFail($cajaId);
    }

    public function crear(Request $request, array $datos): Caja
    {
        return DB::transaction(function () use ($request, $datos): Caja {
            $this->validarSucursalActiva((int) $datos['caj_scl_id']);
            $almacenId = $this->resolverAlmacenCaja((int) $datos['caj_scl_id'], $datos['caj_alm_id'] ?? null);
            $usuarios = $this->validarUsuariosActivos($datos['usuarios']);

            $caja = Caja::query()->create([
                'caj_scl_id' => (int) $datos['caj_scl_id'],
                'caj_alm_id' => $almacenId,
                'caj_nombre' => $datos['caj_nombre'],
                'caj_clave' => $this->generarClaveInterna((int) $datos['caj_scl_id'], (string) $datos['caj_nombre']),
                'caj_estatus' => $datos['caj_estatus'],
                'caj_created_by_usr_id' => optional($request->user())->usr_id,
                'caj_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarUsuarios($caja->caj_id, $usuarios->pluck('usr_id')->all());

            $this->auditoriaService->registrarAccion(
                $request,
                'caja.crear',
                'tbl_cajas_caj',
                (string) $caja->caj_id,
                [
                    'caj_scl_id' => $caja->caj_scl_id,
                    'caj_clave' => $caja->caj_clave,
                    'caj_estatus' => $caja->caj_estatus,
                    'usuarios' => $usuarios->pluck('usr_usuario')->all(),
                ]
            );

            return $caja;
        });
    }

    public function actualizar(Request $request, int $cajaId, array $datos): Caja
    {
        return DB::transaction(function () use ($request, $cajaId, $datos): Caja {
            $caja = Caja::query()->findOrFail($cajaId);
            $this->validarSucursalActiva((int) $datos['caj_scl_id']);
            $almacenId = $this->resolverAlmacenCaja((int) $datos['caj_scl_id'], $datos['caj_alm_id'] ?? null);
            $usuarios = $this->validarUsuariosActivos($datos['usuarios']);

            $caja->update([
                'caj_scl_id' => (int) $datos['caj_scl_id'],
                'caj_alm_id' => $almacenId,
                'caj_nombre' => $datos['caj_nombre'],
                'caj_estatus' => $datos['caj_estatus'],
                'caj_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarUsuarios($caja->caj_id, $usuarios->pluck('usr_id')->all());

            $this->auditoriaService->registrarAccion(
                $request,
                'caja.editar',
                'tbl_cajas_caj',
                (string) $caja->caj_id,
                [
                    'caj_scl_id' => $caja->caj_scl_id,
                    'caj_estatus' => $caja->caj_estatus,
                    'usuarios' => $usuarios->pluck('usr_usuario')->all(),
                ]
            );

            return $caja;
        });
    }

    public function cambiarEstatus(Request $request, int $cajaId, string $estatus): Caja
    {
        $caja = Caja::query()->findOrFail($cajaId);

        $caja->update([
            'caj_estatus' => $estatus,
            'caj_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'caja.activar' : 'caja.inactivar',
            'tbl_cajas_caj',
            (string) $caja->caj_id,
            ['caj_estatus' => $estatus]
        );

        return $caja;
    }

    public function eliminar(Request $request, int $cajaId): void
    {
        DB::transaction(function () use ($request, $cajaId): void {
            $caja = Caja::query()->findOrFail($cajaId);

            CajaUsuario::query()
                ->where('cju_caj_id', $caja->caj_id)
                ->where('cju_deleted', false)
                ->whereNull('cju_deleted_at')
                ->update([
                    'cju_estatus' => 'inactivo',
                    'cju_deleted' => true,
                    'cju_deleted_at' => now(),
                    'cju_updated_at' => now(),
                ]);

            $caja->forceFill([
                'caj_estatus' => 'inactivo',
                'caj_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $caja->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'caja.eliminar',
                'tbl_cajas_caj',
                (string) $caja->caj_id,
                [
                    'caj_clave' => $caja->caj_clave,
                    'caj_estatus' => $caja->caj_estatus,
                ]
            );
        });
    }

    private function validarSucursalActiva(int $sucursalId): void
    {
        $activa = Sucursal::query()
            ->where('scl_id', $sucursalId)
            ->where('scl_estatus', 'activo')
            ->exists();

        if (!$activa) {
            throw ValidationException::withMessages([
                'caj_scl_id' => 'La sucursal seleccionada no está activa o no existe.',
            ]);
        }
    }

    private function validarUsuariosActivos(array $usuarioIds)
    {
        $ids = collect($usuarioIds)->map(fn ($id) => (int) $id)->unique()->values();

        $usuarios = Usuario::query()
            ->whereIn('usr_id', $ids)
            ->where('usr_estatus', 'activo')
            ->get(['usr_id', 'usr_usuario']);

        if ($usuarios->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'usuarios' => 'Todos los usuarios asignados deben existir y estar activos.',
            ]);
        }

        return $usuarios;
    }

    private function sincronizarUsuarios(int $cajaId, array $usuarioIds): void
    {
        CajaUsuario::query()
            ->where('cju_caj_id', $cajaId)
            ->where('cju_deleted', false)
            ->whereNull('cju_deleted_at')
            ->whereNotIn('cju_usr_id', $usuarioIds)
            ->update([
                'cju_estatus' => 'inactivo',
                'cju_deleted' => true,
                'cju_deleted_at' => now(),
                'cju_updated_at' => now(),
            ]);

        foreach ($usuarioIds as $usuarioId) {
            $registro = CajaUsuario::query()
                ->where('cju_caj_id', $cajaId)
                ->where('cju_usr_id', (int) $usuarioId)
                ->first();

            if ($registro) {
                $registro->update([
                    'cju_estatus' => 'activo',
                    'cju_deleted' => false,
                    'cju_deleted_at' => null,
                ]);
                continue;
            }

            CajaUsuario::query()->create([
                'cju_caj_id' => $cajaId,
                'cju_usr_id' => (int) $usuarioId,
                'cju_estatus' => 'activo',
                'cju_deleted' => false,
                'cju_deleted_at' => null,
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

        $base = 'CAJ_' . $sucursalId . '_' . ($limpio !== '' ? $limpio : 'CAJA');
        $base = Str::substr($base, 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while (
            Caja::query()
                ->where('caj_scl_id', $sucursalId)
                ->where('caj_clave', $candidato)
                ->where('caj_deleted', false)
                ->whereNull('caj_deleted_at')
                ->exists()
        ) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function resolverAlmacenCaja(int $sucursalId, mixed $almacenId): ?int
    {
        if (!$almacenId) {
            return null;
        }

        $id = (int) $almacenId;
        $ok = Almacen::query()
            ->where('alm_id', $id)
            ->where('alm_scl_id', $sucursalId)
            ->where('alm_estatus', 'activo')
            ->exists();

        if (!$ok) {
            throw ValidationException::withMessages([
                'caj_alm_id' => 'El almacén seleccionado no está activo o no pertenece a la sucursal de la caja.',
            ]);
        }

        return $id;
    }
}
