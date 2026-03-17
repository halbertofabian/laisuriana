<?php

namespace App\Services\Operacion\Comercial;

use App\Models\Proveedor;
use App\Models\ProveedorContacto;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProveedorService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Proveedor::query()
            ->with(['contactos:prc_id,prc_prv_id,prc_numero,prc_orden'])
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($subQuery) use ($buscar): void {
                    $subQuery->where('prv_nombre_empresa', 'like', "%{$buscar}%")
                        ->orWhere('prv_nombre_asesor_ventas', 'like', "%{$buscar}%")
                        ->orWhere('prv_categoria', 'like', "%{$buscar}%")
                        ->orWhere('prv_rfc', 'like', "%{$buscar}%")
                        ->orWhere('prv_razon_social', 'like', "%{$buscar}%")
                        ->orWhere('prv_correo', 'like', "%{$buscar}%")
                        ->orWhereHas('contactos', fn ($contactos) => $contactos->where('prc_numero', 'like', "%{$buscar}%"));
                });
            })
            ->when(!empty($filtros['estatus']), fn ($query) => $query->where('prv_estatus', $filtros['estatus']))
            ->orderBy('prv_nombre_empresa')
            ->get();
    }

    public function obtenerPorId(int $proveedorId): Proveedor
    {
        return Proveedor::query()
            ->with(['contactos:prc_id,prc_prv_id,prc_numero,prc_orden'])
            ->findOrFail($proveedorId);
    }

    public function crear(Request $request, array $datos): Proveedor
    {
        return DB::transaction(function () use ($request, $datos): Proveedor {
            $proveedor = Proveedor::query()->create([
                'prv_clave' => $this->generarClaveInterna($datos['prv_nombre_empresa']),
                'prv_nombre_empresa' => $datos['prv_nombre_empresa'],
                'prv_nombre_asesor_ventas' => Arr::get($datos, 'prv_nombre_asesor_ventas'),
                'prv_categoria' => Arr::get($datos, 'prv_categoria'),
                'prv_razon_social' => Arr::get($datos, 'prv_razon_social'),
                'prv_rfc' => Arr::get($datos, 'prv_rfc'),
                'prv_correo' => Arr::get($datos, 'prv_correo'),
                'prv_condiciones_pago' => Arr::get($datos, 'prv_condiciones_pago'),
                'prv_tiempo_respuesta' => Arr::get($datos, 'prv_tiempo_respuesta'),
                'prv_estatus' => Arr::get($datos, 'prv_estatus', 'activo'),
                'prv_created_by_usr_id' => optional($request->user())->usr_id,
                'prv_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            $this->sincronizarContactos($request, $proveedor->prv_id, $datos['numeros_contacto'] ?? []);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.proveedor.crear',
                'tbl_proveedores_prv',
                (string) $proveedor->prv_id,
                [
                    'prv_clave' => $proveedor->prv_clave,
                    'prv_rfc' => $proveedor->prv_rfc,
                    'prv_estatus' => $proveedor->prv_estatus,
                ]
            );

            return $proveedor->fresh(['contactos']);
        });
    }

    public function actualizar(Request $request, int $proveedorId, array $datos): Proveedor
    {
        return DB::transaction(function () use ($request, $proveedorId, $datos): Proveedor {
            $proveedor = Proveedor::query()->findOrFail($proveedorId);

            $proveedor->update([
                'prv_nombre_empresa' => $datos['prv_nombre_empresa'],
                'prv_nombre_asesor_ventas' => Arr::get($datos, 'prv_nombre_asesor_ventas'),
                'prv_categoria' => Arr::get($datos, 'prv_categoria'),
                'prv_razon_social' => Arr::get($datos, 'prv_razon_social'),
                'prv_rfc' => Arr::get($datos, 'prv_rfc'),
                'prv_correo' => Arr::get($datos, 'prv_correo'),
                'prv_condiciones_pago' => Arr::get($datos, 'prv_condiciones_pago'),
                'prv_tiempo_respuesta' => Arr::get($datos, 'prv_tiempo_respuesta'),
                'prv_estatus' => Arr::get($datos, 'prv_estatus', $proveedor->prv_estatus ?: 'activo'),
                'prv_updated_by_usr_id' => optional($request->user())->usr_id,
            ]);

            if (!$proveedor->prv_clave) {
                $proveedor->update([
                    'prv_clave' => $this->generarClaveInterna($proveedor->prv_nombre_empresa, $proveedor->prv_id),
                    'prv_updated_by_usr_id' => optional($request->user())->usr_id,
                ]);
            }

            $this->sincronizarContactos($request, $proveedor->prv_id, $datos['numeros_contacto'] ?? []);

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.proveedor.editar',
                'tbl_proveedores_prv',
                (string) $proveedor->prv_id,
                [
                    'prv_clave' => $proveedor->prv_clave,
                    'prv_rfc' => $proveedor->prv_rfc,
                    'prv_estatus' => $proveedor->prv_estatus,
                ]
            );

            return $proveedor->fresh(['contactos']);
        });
    }

    public function cambiarEstatus(Request $request, int $proveedorId, string $estatus): Proveedor
    {
        $proveedor = Proveedor::query()->findOrFail($proveedorId);

        $proveedor->update([
            'prv_estatus' => $estatus,
            'prv_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'catalogo_comercial.proveedor.activar' : 'catalogo_comercial.proveedor.inactivar',
            'tbl_proveedores_prv',
            (string) $proveedor->prv_id,
            ['prv_estatus' => $proveedor->prv_estatus]
        );

        return $proveedor;
    }

    public function eliminar(Request $request, int $proveedorId): void
    {
        DB::transaction(function () use ($request, $proveedorId): void {
            $proveedor = Proveedor::query()->findOrFail($proveedorId);

            ProveedorContacto::query()
                ->where('prc_prv_id', $proveedor->prv_id)
                ->where('prc_deleted', false)
                ->whereNull('prc_deleted_at')
                ->update([
                    'prc_estatus' => 'inactivo',
                    'prc_deleted' => true,
                    'prc_deleted_at' => now(),
                    'prc_updated_by_usr_id' => optional($request->user())->usr_id,
                    'prc_updated_at' => now(),
                ]);

            $proveedor->forceFill([
                'prv_estatus' => 'inactivo',
                'prv_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();

            $proveedor->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'catalogo_comercial.proveedor.eliminar',
                'tbl_proveedores_prv',
                (string) $proveedor->prv_id,
                [
                    'prv_clave' => $proveedor->prv_clave,
                    'prv_rfc' => $proveedor->prv_rfc,
                ]
            );
        });
    }

    private function sincronizarContactos(Request $request, int $proveedorId, array $numerosContacto): void
    {
        $numerosNormalizados = $this->normalizarNumerosContacto($numerosContacto);

        $queryDesactivar = ProveedorContacto::query()
            ->where('prc_prv_id', $proveedorId)
            ->where('prc_deleted', false)
            ->whereNull('prc_deleted_at');

        if (!empty($numerosNormalizados)) {
            $queryDesactivar->whereNotIn('prc_numero', $numerosNormalizados);
        }

        $queryDesactivar->update([
            'prc_estatus' => 'inactivo',
            'prc_deleted' => true,
            'prc_deleted_at' => now(),
            'prc_updated_by_usr_id' => optional($request->user())->usr_id,
            'prc_updated_at' => now(),
        ]);

        foreach ($numerosNormalizados as $index => $numero) {
            $contacto = ProveedorContacto::query()
                ->withoutGlobalScopes()
                ->where('prc_prv_id', $proveedorId)
                ->where('prc_numero', $numero)
                ->first();

            $payload = [
                'prc_orden' => $index + 1,
                'prc_estatus' => 'activo',
                'prc_updated_by_usr_id' => optional($request->user())->usr_id,
            ];

            if ($contacto) {
                $contacto->forceFill(array_merge($payload, [
                    'prc_deleted' => false,
                    'prc_deleted_at' => null,
                ]))->save();
                continue;
            }

            ProveedorContacto::query()->create(array_merge($payload, [
                'prc_prv_id' => $proveedorId,
                'prc_numero' => $numero,
                'prc_created_by_usr_id' => optional($request->user())->usr_id,
            ]));
        }
    }

    private function normalizarNumerosContacto(array $numerosContacto): array
    {
        return collect($numerosContacto)
            ->map(fn ($numero) => trim((string) $numero))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function generarClaveInterna(string $nombreEmpresa, ?int $idIgnorar = null): string
    {
        $limpio = (string) Str::of($nombreEmpresa)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->upper();

        $base = Str::substr('PRV_' . ($limpio !== '' ? $limpio : 'PROVEEDOR'), 0, 40);
        $candidato = $base;
        $consecutivo = 2;

        while ($this->claveExiste($candidato, $idIgnorar)) {
            $sufijo = '_' . $consecutivo;
            $candidato = Str::substr($base, 0, 40 - strlen($sufijo)) . $sufijo;
            $consecutivo++;
        }

        return $candidato;
    }

    private function claveExiste(string $clave, ?int $idIgnorar = null): bool
    {
        $query = Proveedor::query()->withoutGlobalScopes()->where('prv_clave', $clave);

        if ($idIgnorar) {
            $query->where('prv_id', '!=', $idIgnorar);
        }

        return $query->exists();
    }
}
