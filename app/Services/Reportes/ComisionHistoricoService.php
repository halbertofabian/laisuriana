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
        $lineaGrupos = DB::table('tbl_comision_v2_periodo_lineas_cml')
            ->where('cml_cmp_id', $periodo->cmp_id)
            ->pluck('cml_cpd_id', 'cml_lna_id')->all();

        return $this->referencia((int) $periodo->cmp_scl_id, $mes, $periodo->almacenes->pluck('alm_id')->all(), $lineaGrupos, $movimientos);
    }

    /**
     * Referencia histórica por grupo (departamento) y su cobertura por almacén y línea.
     * Cada captura representa el total mensual y sustituye solo su combinación del POS.
     */
    public function referencia(int $sucursalId, Carbon $mes, array $almacenIds, array $lineaGrupos, Collection $movimientos): Collection
    {
        $almacenIds = array_values(array_unique(array_map('intval', $almacenIds)));
        $capturas = $almacenIds === [] || $lineaGrupos === [] ? collect() : DB::table('tbl_comision_historicos_chv')
            ->where('chv_scl_id', $sucursalId)
            ->whereDate('chv_periodo', $mes->copy()->startOfMonth()->toDateString())
            ->whereIn('chv_alm_id', $almacenIds)
            ->whereIn('chv_lna_id', array_map('intval', array_keys($lineaGrupos)))
            ->get(['chv_id', 'chv_alm_id', 'chv_lna_id', 'chv_ventas_netas', 'chv_autoservicio', 'chv_referencia'])
            ->keyBy(fn ($fila) => $fila->chv_alm_id.'|'.$fila->chv_lna_id);
        $claveMovimiento = fn ($fila) => ($fila->almacen_id ?? '').'|'.($fila->linea_id ?? '');
        $pos = $movimientos->groupBy($claveMovimiento);
        $almacenes = DB::table('tbl_almacenes_alm')->whereIn('alm_id', $almacenIds)->pluck('alm_nombre', 'alm_id');
        $lineas = DB::table('tbl_lineas_lna')->whereIn('lna_id', array_keys($lineaGrupos))->pluck('lna_nombre', 'lna_id');

        $resumen = collect();
        foreach (array_unique(array_values($lineaGrupos)) as $grupo) {
            $filas = $movimientos->where('departamento_periodo_id', $grupo)
                ->reject(fn ($fila) => $capturas->has($claveMovimiento($fila)));
            $capturasGrupo = $capturas->filter(fn ($fila) => ($lineaGrupos[$fila->chv_lna_id] ?? null) == $grupo);
            $combinaciones = [];
            foreach ($almacenIds as $almacenId) {
                foreach (array_keys($lineaGrupos, $grupo) as $lineaId) {
                    $clave = $almacenId.'|'.$lineaId;
                    $captura = $capturas->get($clave);
                    $sistema = $pos->get($clave, collect());
                    $ventasSistema = round((float) $sistema->sum('importe'), 2);
                    $autoSistema = round((float) $sistema->whereNull('vendedor_id')->sum('importe'), 2);
                    $fuente = $captura ? 'manual' : ($sistema->isNotEmpty() ? 'sistema' : 'sin_datos');
                    $ventas = $captura ? (float) $captura->chv_ventas_netas : $ventasSistema;
                    $autoservicio = $captura ? (float) $captura->chv_autoservicio : $autoSistema;
                    $combinaciones[] = [
                        'almacen_id' => $almacenId,
                        'almacen' => (string) ($almacenes[$almacenId] ?? ''),
                        'linea_id' => (int) $lineaId,
                        'linea' => (string) ($lineas[$lineaId] ?? ''),
                        'fuente' => $fuente,
                        'ventas' => $ventas,
                        'autoservicio' => $autoservicio,
                        'base' => round($ventas - $autoservicio, 2),
                        'captura_id' => $captura ? (int) $captura->chv_id : null,
                        'documento' => $captura?->chv_referencia,
                        'sistema_sustituido' => $captura && $sistema->isNotEmpty() ? $ventasSistema : null,
                    ];
                }
            }
            $ventasSistema = round((float) $filas->sum('importe'), 2);
            $ventasManual = round((float) $capturasGrupo->sum('chv_ventas_netas'), 2);
            $ventas = round($ventasSistema + $ventasManual, 2);
            $autoservicio = round((float) $filas->whereNull('vendedor_id')->sum('importe') + (float) $capturasGrupo->sum('chv_autoservicio'), 2);
            $hayManual = $capturasGrupo->isNotEmpty();
            $haySistema = $filas->isNotEmpty();
            $resumen->put($grupo, [
                'ventas' => $ventas,
                'autoservicio' => $autoservicio,
                'base' => round($ventas - $autoservicio, 2),
                'ventas_sistema' => $ventasSistema,
                'ventas_manual' => $ventasManual,
                'origen' => $hayManual && $haySistema ? 'mixto' : ($hayManual ? 'manual' : ($haySistema ? 'sistema' : null)),
                'combinaciones' => $combinaciones,
                'faltantes' => collect($combinaciones)->where('fuente', 'sin_datos')->count(),
            ]);
        }

        return $resumen;
    }
}
