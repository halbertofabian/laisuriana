<?php

namespace App\Services\Operacion;

use App\Models\Cliente;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteService
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function listar(array $filtros = [])
    {
        return Cliente::query()
            ->when(!empty($filtros['buscar']), function ($query) use ($filtros): void {
                $buscar = trim((string) $filtros['buscar']);
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('cli_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cli_apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('cli_apellido_materno', 'like', "%{$buscar}%")
                        ->orWhere('cli_rfc', 'like', "%{$buscar}%")
                        ->orWhere('cli_curp', 'like', "%{$buscar}%")
                        ->orWhere('cli_email', 'like', "%{$buscar}%")
                        ->orWhere('cli_telefono', 'like', "%{$buscar}%");
                });
            })
            ->when(!empty($filtros['estatus']), function ($query) use ($filtros): void {
                $query->where('cli_estatus', $filtros['estatus']);
            })
            ->orderBy('cli_nombre')
            ->orderBy('cli_apellido_paterno')
            ->orderBy('cli_apellido_materno')
            ->get();
    }

    public function obtenerPorId(int $clienteId): Cliente
    {
        return Cliente::query()->findOrFail($clienteId);
    }

    public function crear(Request $request, array $datos): Cliente
    {
        return DB::transaction(function () use ($request, $datos): Cliente {
            $datos['cli_created_by_usr_id'] = optional($request->user())->usr_id;
            $datos['cli_updated_by_usr_id'] = optional($request->user())->usr_id;
            $cliente = Cliente::query()->create($datos);

            $this->auditoriaService->registrarAccion(
                $request,
                'cliente.crear',
                'tbl_clientes_cli',
                (string) $cliente->cli_id,
                ['cli_nombre' => $cliente->cli_nombre, 'cli_email' => $cliente->cli_email]
            );

            return $cliente;
        });
    }

    public function actualizar(Request $request, int $clienteId, array $datos): Cliente
    {
        return DB::transaction(function () use ($request, $clienteId, $datos): Cliente {
            $cliente = Cliente::query()->findOrFail($clienteId);
            $datos['cli_updated_by_usr_id'] = optional($request->user())->usr_id;
            $cliente->update($datos);

            $this->auditoriaService->registrarAccion(
                $request,
                'cliente.editar',
                'tbl_clientes_cli',
                (string) $cliente->cli_id,
                ['cli_nombre' => $cliente->cli_nombre, 'cli_email' => $cliente->cli_email]
            );

            return $cliente;
        });
    }

    public function cambiarEstatus(Request $request, int $clienteId, string $estatus): Cliente
    {
        $cliente = Cliente::query()->findOrFail($clienteId);
        $cliente->update([
            'cli_estatus' => $estatus,
            'cli_updated_by_usr_id' => optional($request->user())->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            $estatus === 'activo' ? 'cliente.activar' : 'cliente.inactivar',
            'tbl_clientes_cli',
            (string) $cliente->cli_id,
            ['cli_estatus' => $estatus]
        );

        return $cliente;
    }

    public function eliminar(Request $request, int $clienteId): void
    {
        DB::transaction(function () use ($request, $clienteId): void {
            $cliente = Cliente::query()->findOrFail($clienteId);
            $cliente->forceFill([
                'cli_estatus' => 'inactivo',
                'cli_updated_by_usr_id' => optional($request->user())->usr_id,
            ])->save();
            $cliente->marcarComoEliminado();

            $this->auditoriaService->registrarAccion(
                $request,
                'cliente.eliminar',
                'tbl_clientes_cli',
                (string) $cliente->cli_id,
                ['cli_nombre' => $cliente->cli_nombre]
            );
        });
    }
}

