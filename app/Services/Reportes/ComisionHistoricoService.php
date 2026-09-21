<?php

namespace App\Services\Reportes;

use App\Models\ComisionV2Periodo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionHistoricoService
{
    public function guardar(array $datos, int $sucursalId, int $usuarioId): array
    {
        return DB::transaction(function () use ($datos, $sucursalId, $usuarioId) {
            // Serializa las capturas de la sucursal, también cuando la combinación aún no existe.
            DB::table('tbl_sucursales_scl')->where('scl_id', $sucursalId)->lockForUpdate()->firstOrFail();
            $antes = null;
            if (! empty($datos['id'])) {
                $antes = DB::table('tbl_comision_historicos_chv')->where('chv_scl_id', $sucursalId)->where('chv_id', $datos['id'])->lockForUpdate()->first();
                abort_unless($antes, 404);
                if ((int) $antes->chv_version !== (int) $datos['version']) {
                    throw ValidationException::withMessages(['version' => 'Otra persona modificó este registro. Ábrelo de nuevo antes de corregirlo.']);
                }
            }

            $periodo = Carbon::createFromFormat('Y-m', $datos['periodo'])->startOfMonth()->toDateString();
            $duplicado = DB::table('tbl_comision_historicos_chv')
                ->where('chv_scl_id', $sucursalId)->where('chv_periodo', $periodo)
                ->where('chv_alm_id', $datos['almacen_id'])->where('chv_lna_id', $datos['linea_id'])
                ->when($antes, fn ($q) => $q->where('chv_id', '!=', $antes->chv_id))->exists();
            if ($duplicado) {
                throw ValidationException::withMessages(['linea_id' => 'Ya hay una captura para este mes, almacén y línea. Abre su opción Corregir en la lista.']);
            }
            $registro = [
                'chv_scl_id' => $sucursalId, 'chv_periodo' => $periodo,
                'chv_alm_id' => (int) $datos['almacen_id'], 'chv_lna_id' => (int) $datos['linea_id'],
                'chv_ventas_netas' => $datos['ventas_netas'], 'chv_autoservicio' => $datos['autoservicio'],
                'chv_referencia' => trim($datos['referencia']), 'chv_observaciones' => trim($datos['observaciones'] ?? '') ?: null,
                'chv_version' => ($antes->chv_version ?? 0) + 1,
                'chv_updated_by_usr_id' => $usuarioId, 'chv_updated_at' => now(),
            ];
            if ($antes) {
                $id = $antes->chv_id;
                DB::table('tbl_comision_historicos_chv')->where('chv_id', $id)->update($registro);
            } else {
                $id = DB::table('tbl_comision_historicos_chv')->insertGetId($registro + ['chv_created_by_usr_id' => $usuarioId, 'chv_created_at' => now()]);
            }

            return ['id' => $id, 'antes' => $antes ? (array) $antes : null, 'despues' => $registro];
        });
    }

    public function resumirReferencia(ComisionV2Periodo $periodo, Collection $movimientos, Carbon $mes): Collection
    {
        $capturas = DB::table('tbl_comision_historicos_chv as chv')
            ->join('tbl_comision_v2_periodo_lineas_cml as cml', function ($join) use ($periodo) {
                $join->on('cml.cml_lna_id', '=', 'chv.chv_lna_id')->where('cml.cml_cmp_id', $periodo->cmp_id);
            })
            ->where('chv.chv_scl_id', $periodo->cmp_scl_id)
            ->whereDate('chv.chv_periodo', $mes->copy()->startOfMonth()->toDateString())
            ->whereIn('chv.chv_alm_id', $periodo->almacenes->pluck('alm_id'))
            ->get(['chv.*', 'cml.cml_cpd_id']);
        $claves = $capturas->mapWithKeys(fn ($fila) => [$fila->chv_alm_id.'|'.$fila->chv_lna_id => true]);

        // Cada captura representa el total mensual; sustituye solo esa combinación del POS.
        $filas = $movimientos->reject(fn ($fila) => $claves->has(($fila->almacen_id ?? '').'|'.($fila->linea_id ?? '')))
            ->map(fn ($fila) => [
                'departamento_id' => $fila->departamento_periodo_id,
                'ventas' => (float) $fila->importe,
                'autoservicio' => $fila->vendedor_id === null ? (float) $fila->importe : 0,
            ])->concat($capturas->map(fn ($fila) => [
                'departamento_id' => $fila->cml_cpd_id,
                'ventas' => (float) $fila->chv_ventas_netas,
                'autoservicio' => (float) $fila->chv_autoservicio,
            ]));

        return $filas->groupBy('departamento_id')->map(fn ($grupo) => [
            'ventas' => round($grupo->sum('ventas'), 2),
            'autoservicio' => round($grupo->sum('autoservicio'), 2),
        ]);
    }
}
