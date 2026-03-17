<?php

namespace App\Services;

use App\Models\BitacoraAcceso;
use App\Models\BitacoraAccion;

class BitacoraService
{
    public function listarAccesos(array $filtros = [])
    {
        return BitacoraAcceso::query()
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'tbl_bitacora_accesos_bac.bac_usr_id')
            ->when(!empty($filtros['resultado']), function ($query) use ($filtros): void {
                $query->where('tbl_bitacora_accesos_bac.bac_resultado', $filtros['resultado']);
            })
            ->when(!empty($filtros['usuario']), function ($query) use ($filtros): void {
                $valor = trim((string) $filtros['usuario']);
                $query->where(function ($sub) use ($valor): void {
                    $sub->where('tbl_bitacora_accesos_bac.bac_usuario_intentado', 'like', "%{$valor}%")
                        ->orWhere('usr.usr_usuario', 'like', "%{$valor}%")
                        ->orWhere('usr.usr_nombre', 'like', "%{$valor}%");
                });
            })
            ->when(!empty($filtros['fecha_desde']), function ($query) use ($filtros): void {
                $query->whereDate('tbl_bitacora_accesos_bac.bac_created_at', '>=', $filtros['fecha_desde']);
            })
            ->when(!empty($filtros['fecha_hasta']), function ($query) use ($filtros): void {
                $query->whereDate('tbl_bitacora_accesos_bac.bac_created_at', '<=', $filtros['fecha_hasta']);
            })
            ->orderByDesc('tbl_bitacora_accesos_bac.bac_created_at')
            ->get([
                'tbl_bitacora_accesos_bac.bac_id',
                'tbl_bitacora_accesos_bac.bac_usuario_intentado',
                'tbl_bitacora_accesos_bac.bac_resultado',
                'tbl_bitacora_accesos_bac.bac_motivo',
                'tbl_bitacora_accesos_bac.bac_ip',
                'tbl_bitacora_accesos_bac.bac_user_agent',
                'tbl_bitacora_accesos_bac.bac_created_at',
                'usr.usr_usuario as usuario_registrado',
                'usr.usr_nombre as nombre_registrado',
            ]);
    }

    public function listarAcciones(array $filtros = [])
    {
        return BitacoraAccion::query()
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'tbl_bitacora_acciones_bac.bac_usr_id')
            ->leftJoin('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'tbl_bitacora_acciones_bac.bac_scl_id')
            ->when(!empty($filtros['accion']), function ($query) use ($filtros): void {
                $query->where('tbl_bitacora_acciones_bac.bac_accion', 'like', '%' . trim((string) $filtros['accion']) . '%');
            })
            ->when(!empty($filtros['entidad']), function ($query) use ($filtros): void {
                $query->where('tbl_bitacora_acciones_bac.bac_entidad', 'like', '%' . trim((string) $filtros['entidad']) . '%');
            })
            ->when(!empty($filtros['usuario']), function ($query) use ($filtros): void {
                $valor = trim((string) $filtros['usuario']);
                $query->where(function ($sub) use ($valor): void {
                    $sub->where('usr.usr_usuario', 'like', "%{$valor}%")
                        ->orWhere('usr.usr_nombre', 'like', "%{$valor}%");
                });
            })
            ->when(!empty($filtros['fecha_desde']), function ($query) use ($filtros): void {
                $query->whereDate('tbl_bitacora_acciones_bac.bac_created_at', '>=', $filtros['fecha_desde']);
            })
            ->when(!empty($filtros['fecha_hasta']), function ($query) use ($filtros): void {
                $query->whereDate('tbl_bitacora_acciones_bac.bac_created_at', '<=', $filtros['fecha_hasta']);
            })
            ->orderByDesc('tbl_bitacora_acciones_bac.bac_created_at')
            ->get([
                'tbl_bitacora_acciones_bac.bac_id',
                'tbl_bitacora_acciones_bac.bac_accion',
                'tbl_bitacora_acciones_bac.bac_entidad',
                'tbl_bitacora_acciones_bac.bac_entidad_id',
                'tbl_bitacora_acciones_bac.bac_payload',
                'tbl_bitacora_acciones_bac.bac_ip',
                'tbl_bitacora_acciones_bac.bac_user_agent',
                'tbl_bitacora_acciones_bac.bac_created_at',
                'usr.usr_usuario as usuario_registrado',
                'usr.usr_nombre as nombre_registrado',
                'scl.scl_nombre as sucursal_nombre',
            ]);
    }
}
