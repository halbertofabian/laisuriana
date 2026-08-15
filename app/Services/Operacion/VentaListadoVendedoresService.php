<?php

namespace App\Services\Operacion;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VentaListadoVendedoresService
{
    public function agregar(Collection $ventas): Collection
    {
        $ventaIds = $ventas->pluck('psv_id')->map(fn ($id): int => (int) $id)->all();
        if ($ventaIds === []) {
            return $ventas;
        }

        $partidas = DB::table('tbl_pos_venta_detalle_pvd as pvd')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pvd.pvd_usr_id')
            ->whereIn('pvd.pvd_psv_id', $ventaIds)
            ->where('pvd.pvd_deleted', false)
            ->whereNull('pvd.pvd_deleted_at')
            ->orderBy('pvd.pvd_id')
            ->get([
                'pvd.pvd_psv_id as venta_id',
                'pvd.pvd_usr_id as vendedor_id',
                'usr.usr_nombre as vendedor_nombre',
                'usr.usr_usuario as vendedor_usuario',
            ]);
        $devoluciones = DB::table('tbl_pos_cambios_detalle_pcd as pcd')
            ->join('tbl_pos_venta_detalle_pvd as origen', 'origen.pvd_id', '=', 'pcd.pcd_pvd_origen_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'origen.pvd_usr_id')
            ->whereIn('pcd.pcd_psv_id', $ventaIds)
            ->where('pcd.pcd_deleted', false)
            ->whereNull('pcd.pcd_deleted_at')
            ->where('origen.pvd_deleted', false)
            ->whereNull('origen.pvd_deleted_at')
            ->orderBy('pcd.pcd_id')
            ->get([
                'pcd.pcd_psv_id as venta_id',
                'origen.pvd_usr_id as vendedor_id',
                'usr.usr_nombre as vendedor_nombre',
                'usr.usr_usuario as vendedor_usuario',
            ]);
        $porVenta = $partidas->concat($devoluciones)->groupBy('venta_id');

        return $ventas->map(function ($venta) use ($porVenta) {
            $movimientos = $porVenta->get($venta->psv_id, collect());
            $nombres = $movimientos
                ->filter(fn ($movimiento): bool => $movimiento->vendedor_id !== null)
                ->map(fn ($movimiento): string => trim((string) ($movimiento->vendedor_nombre ?: $movimiento->vendedor_usuario)))
                ->filter()
                ->unique(fn (string $nombre): string => mb_strtolower($nombre))
                ->values();
            if ($movimientos->contains(fn ($movimiento): bool => $movimiento->vendedor_id === null)) {
                $nombres->push('Sin atención');
            }

            $venta->vendedor = $nombres->isEmpty() ? 'Sin atención' : $nombres->implode(', ');

            return $venta;
        });
    }

    public function agregarFiltroBusqueda(Builder $query, string $buscar, string $ventaAlias = 'psv'): void
    {
        $query->orWhereExists(function ($vendedores) use ($buscar, $ventaAlias): void {
            $vendedores->selectRaw('1')
                ->from('tbl_pos_venta_detalle_pvd as pvd_buscar')
                ->join('tbl_usuarios_usr as usr_buscar', 'usr_buscar.usr_id', '=', 'pvd_buscar.pvd_usr_id')
                ->whereColumn('pvd_buscar.pvd_psv_id', $ventaAlias.'.psv_id')
                ->where('pvd_buscar.pvd_deleted', false)
                ->whereNull('pvd_buscar.pvd_deleted_at')
                ->where(function ($usuario) use ($buscar): void {
                    $usuario->where('usr_buscar.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('usr_buscar.usr_usuario', 'like', "%{$buscar}%");
                });
        })->orWhereExists(function ($vendedoresCambio) use ($buscar, $ventaAlias): void {
            $vendedoresCambio->selectRaw('1')
                ->from('tbl_pos_cambios_detalle_pcd as pcd_buscar')
                ->join('tbl_pos_venta_detalle_pvd as origen_buscar', 'origen_buscar.pvd_id', '=', 'pcd_buscar.pcd_pvd_origen_id')
                ->join('tbl_usuarios_usr as usr_cambio', 'usr_cambio.usr_id', '=', 'origen_buscar.pvd_usr_id')
                ->whereColumn('pcd_buscar.pcd_psv_id', $ventaAlias.'.psv_id')
                ->where('pcd_buscar.pcd_deleted', false)
                ->whereNull('pcd_buscar.pcd_deleted_at')
                ->where('origen_buscar.pvd_deleted', false)
                ->whereNull('origen_buscar.pvd_deleted_at')
                ->where(function ($usuario) use ($buscar): void {
                    $usuario->where('usr_cambio.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('usr_cambio.usr_usuario', 'like', "%{$buscar}%");
                });
        });
    }
}
