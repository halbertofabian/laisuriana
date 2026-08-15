<?php

namespace App\Services\Reportes;

use App\Models\ComisionPeriodo;
use App\Models\ComisionResultado;
use App\Models\ComisionResultadoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComisionCalculoService
{
    public function calcular(ComisionPeriodo $periodo, int $usuarioId): int
    {
        return DB::transaction(function () use ($periodo, $usuarioId): int {
            $periodo = ComisionPeriodo::query()->lockForUpdate()->findOrFail($periodo->cpe_id);
            if ($periodo->cpe_estatus === 'cerrado') {
                throw ValidationException::withMessages(['periodo' => 'El periodo está cerrado y no puede recalcularse.']);
            }

            $periodo->load(['almacenes', 'configuracionesGrupo.grupo', 'ajustes']);
            if ($periodo->almacenes->isEmpty()) {
                throw ValidationException::withMessages(['periodo' => 'Configura al menos un almacén para calcular comisiones.']);
            }
            if ($periodo->configuracionesGrupo->isEmpty()) {
                throw ValidationException::withMessages(['periodo' => 'Configura los grupos de comisión para el periodo.']);
            }

            $movimientos = $this->movimientosDetallados($periodo);
            $ajustes = $periodo->ajustes->keyBy('cav_cve_id');
            $vendedores = DB::table('tbl_comision_periodo_vendedores_cpv as cpv')
                ->join('tbl_comision_vendedores_cve as cve', 'cve.cve_id', '=', 'cpv.cpv_cve_id')
                ->join('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'cve.cve_usr_id')
                ->join('tbl_comision_grupos_cgr as cgr', 'cgr.cgr_id', '=', 'cpv.cpv_cgr_id')
                ->where('cpv.cpv_cpe_id', $periodo->cpe_id)
                ->orderBy('cpv.cpv_numero_vendedor')
                ->get([
                    'cve.cve_id', 'cve.cve_usr_id', 'cpv.cpv_cgr_id', 'cpv.cpv_numero_vendedor',
                    'usr.usr_nombre', 'usr.usr_usuario', 'cgr.cgr_nombre',
                ]);

            ComisionResultado::query()->where('crs_cpe_id', $periodo->cpe_id)->delete();

            $metas = [];
            foreach ($periodo->configuracionesGrupo as $configGrupo) {
                $movGrupo = $movimientos->where('grupo_id', (int) $configGrupo->cpg_cgr_id);
                $ventasGrupo = round((float) $movGrupo->sum('importe'), 2);
                $sinAtencion = round((float) $movGrupo->whereNull('vendedor_id')->sum('importe'), 2);
                $baseMeta = round($ventasGrupo - $sinAtencion, 2);
                $promedio = (float) $configGrupo->cpg_vendedores_promedio;
                if ($promedio <= 0) {
                    throw ValidationException::withMessages(['periodo' => "El promedio de vendedores de {$configGrupo->grupo->cgr_nombre} debe ser mayor que cero."]);
                }
                $meta = round(($baseMeta / $promedio) * (1 + ((float) $configGrupo->cpg_incremento_meta / 100)), 2);
                $metas[(int) $configGrupo->cpg_cgr_id] = $meta;
                $configGrupo->update([
                    'cpg_ventas_grupo' => $ventasGrupo,
                    'cpg_ventas_sin_atencion' => $sinAtencion,
                    'cpg_base_meta' => $baseMeta,
                    'cpg_meta_individual' => $meta,
                ]);
            }

            foreach ($vendedores as $vendedor) {
                $grupoId = (int) $vendedor->cpv_cgr_id;
                if (! array_key_exists($grupoId, $metas)) {
                    continue;
                }

                $ventas = round((float) $movimientos
                    ->where('grupo_id', $grupoId)
                    ->where('vendedor_id', (int) $vendedor->cve_usr_id)
                    ->sum('importe'), 2);
                $ajuste = $ajustes->get($vendedor->cve_id);
                $calculo = $this->calcularFila(
                    $ventas,
                    $metas[$grupoId],
                    (float) $periodo->cpe_factor_comisionable,
                    (float) $periodo->cpe_tasa_general,
                    (float) ($ajuste?->cav_ajuste_tasa ?? 0),
                    $ajuste?->cav_tasa_final !== null ? (float) $ajuste->cav_tasa_final : null,
                    (float) $periodo->cpe_cumplimiento_minimo,
                    (float) ($ajuste?->cav_bono ?? 0),
                );

                $observaciones = collect([
                    $ajuste?->cav_motivo,
                    $calculo['cumplimiento'] < (float) $periodo->cpe_cumplimiento_minimo ? 'No alcanzó el cumplimiento mínimo.' : null,
                ])->filter()->implode(' ');

                $resultado = ComisionResultado::query()->create([
                    'crs_cpe_id' => $periodo->cpe_id,
                    'crs_cve_id' => $vendedor->cve_id,
                    'crs_cgr_id' => $grupoId,
                    'crs_numero_vendedor' => $vendedor->cpv_numero_vendedor,
                    'crs_nombre_vendedor' => $vendedor->usr_nombre ?: $vendedor->usr_usuario ?: 'Sin nombre',
                    'crs_grupo_nombre' => $vendedor->cgr_nombre ?: 'Sin grupo',
                    'crs_ventas_totales' => $ventas,
                    'crs_meta' => $metas[$grupoId],
                    'crs_cumplimiento' => $calculo['cumplimiento'],
                    'crs_factor_comisionable' => $periodo->cpe_factor_comisionable,
                    'crs_base_comisionable' => $calculo['base_comisionable'],
                    'crs_tasa_general' => $periodo->cpe_tasa_general,
                    'crs_ajuste_tasa' => $calculo['ajuste_tasa'],
                    'crs_tasa_final' => $calculo['tasa_final'],
                    'crs_comision' => $calculo['comision'],
                    'crs_bono' => $calculo['bono'],
                    'crs_total_pagar' => $calculo['total_pagar'],
                    'crs_observaciones' => $observaciones ?: null,
                ]);

                $detalleVendedor = $movimientos
                    ->where('grupo_id', $grupoId)
                    ->where('vendedor_id', (int) $vendedor->cve_usr_id)
                    ->groupBy(fn ($movimiento) => $movimiento->almacen_id.'|'.$movimiento->linea_id)
                    ->map(function (Collection $filas) use ($resultado): array {
                        $primera = $filas->first();

                        return [
                            'crd_crs_id' => $resultado->crs_id,
                            'crd_alm_id' => $primera->almacen_id,
                            'crd_almacen_nombre' => $primera->almacen_nombre,
                            'crd_lna_id' => $primera->linea_id,
                            'crd_linea_nombre' => $primera->linea_nombre,
                            'crd_venta_bruta' => round((float) $filas->sum('venta_bruta'), 2),
                            'crd_descuentos' => round((float) $filas->sum('descuentos'), 2),
                            'crd_devoluciones' => round((float) $filas->sum('devoluciones'), 2),
                            'crd_venta_neta' => round((float) $filas->sum('importe'), 2),
                            'crd_created_at' => now(),
                            'crd_updated_at' => now(),
                        ];
                    })
                    ->values()
                    ->all();

                if ($detalleVendedor !== []) {
                    ComisionResultadoDetalle::query()->insert($detalleVendedor);
                }
            }

            $periodo->update([
                'cpe_estatus' => 'calculado',
                'cpe_calculado_at' => now(),
                'cpe_calculado_by_usr_id' => $usuarioId,
                'cpe_updated_by_usr_id' => $usuarioId,
            ]);

            return ComisionResultado::query()->where('crs_cpe_id', $periodo->cpe_id)->count();
        });
    }

    public function cerrar(ComisionPeriodo $periodo, int $usuarioId): void
    {
        DB::transaction(function () use ($periodo, $usuarioId): void {
            $periodo = ComisionPeriodo::query()->lockForUpdate()->findOrFail($periodo->cpe_id);
            if ($periodo->cpe_estatus !== 'calculado' || ! $periodo->resultados()->exists()) {
                throw ValidationException::withMessages(['periodo' => 'Calcula el periodo antes de cerrarlo.']);
            }
            $periodo->update([
                'cpe_estatus' => 'cerrado',
                'cpe_cerrado_at' => now(),
                'cpe_cerrado_by_usr_id' => $usuarioId,
                'cpe_updated_by_usr_id' => $usuarioId,
            ]);
        });
    }

    public function calcularFila(
        float $ventas,
        float $meta,
        float $factorComisionable,
        float $tasaGeneral,
        float $ajusteTasa,
        ?float $tasaFinalForzada,
        float $cumplimientoMinimo,
        float $bono = 0,
    ): array {
        $cumplimiento = $meta > 0 ? round(($ventas / $meta) * 100, 2) : 0.0;
        $tasaFinal = max(0, $tasaFinalForzada ?? ($tasaGeneral + $ajusteTasa));
        $baseComisionable = round($ventas * ($factorComisionable / 100), 2);
        $comision = $cumplimiento >= $cumplimientoMinimo
            ? round($baseComisionable * ($tasaFinal / 100), 2)
            : 0.0;
        $bono = round(max(0, $bono), 2);

        return [
            'cumplimiento' => $cumplimiento,
            'base_comisionable' => $baseComisionable,
            'ajuste_tasa' => round($tasaFinal - $tasaGeneral, 4),
            'tasa_final' => round($tasaFinal, 4),
            'comision' => $comision,
            'bono' => $bono,
            'total_pagar' => round($comision + $bono, 2),
        ];
    }

    public function reporte(int $sucursalId, Carbon $desde, array $filtros = []): array
    {
        $periodo = ComisionPeriodo::query()
            ->where('cpe_scl_id', $sucursalId)
            ->whereDate('cpe_periodo', $desde->copy()->startOfMonth()->toDateString())
            ->first();
        $headers = ['No. vendedor', 'Nombre', 'Ventas totales', 'Porcentaje', 'Comisión', 'Bono', 'Grupo', 'Meta', 'Factor comisionable %', 'Tasa %', 'Total pagar', 'Observaciones'];

        if (! $periodo) {
            return [
                'encabezados' => $headers,
                'rows' => collect(),
                'kpis' => ['Estado' => 'Sin configurar', 'Vendedores' => 0, 'Comisiones' => 0, 'Total pagar' => 0],
                'total_registros' => 0,
                'estado_periodo' => 'sin_configurar',
                'detalles' => [],
                'detalle_exportacion' => collect(),
            ];
        }

        $estadoCoincide = empty($filtros['estado']) || $filtros['estado'] === $periodo->cpe_estatus;
        $resultados = ComisionResultado::query()
            ->with(['detalles', 'vendedor:cve_id,cve_usr_id'])
            ->where('crs_cpe_id', $periodo->cpe_id)
            ->when($filtros['grupo_id'] ?? null, fn ($query, $grupoId) => $query->where('crs_cgr_id', $grupoId))
            ->when($filtros['usuario_id'] ?? null, fn ($query, $usuarioId) => $query->whereHas('vendedor', fn ($vendedor) => $vendedor->where('cve_usr_id', $usuarioId)))
            ->when(! $estadoCoincide, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('crs_grupo_nombre')
            ->orderBy('crs_numero_vendedor')
            ->get();

        $rows = $resultados
            ->map(fn (ComisionResultado $r) => (object) [
                'no_vendedor' => $r->crs_numero_vendedor,
                'nombre' => $r->crs_nombre_vendedor,
                'ventas_totales' => (float) $r->crs_ventas_totales,
                'porcentaje' => (float) $r->crs_cumplimiento,
                'comision' => (float) $r->crs_comision,
                'bono' => (float) $r->crs_bono,
                'grupo' => $r->crs_grupo_nombre,
                'meta' => (float) $r->crs_meta,
                'factor_comisionable' => (float) $r->crs_factor_comisionable,
                'tasa' => (float) $r->crs_tasa_final,
                'total_pagar' => (float) $r->crs_total_pagar,
                'observaciones' => $r->crs_observaciones,
            ]);

        $configuracionesGrupo = $periodo->configuracionesGrupo()->get()->keyBy('cpg_cgr_id');
        $detalles = $resultados->mapWithKeys(function (ComisionResultado $resultado) use ($periodo, $configuracionesGrupo): array {
            $configGrupo = $configuracionesGrupo->get($resultado->crs_cgr_id);
            $porAlmacen = $this->agruparDetalle($resultado->detalles, 'crd_almacen_nombre');
            $porLinea = $this->agruparDetalle($resultado->detalles, 'crd_linea_nombre');
            $ajuste = (float) $resultado->crs_ajuste_tasa;
            $explicacionTasa = abs($ajuste) < 0.00001
                ? 'Se aplicó la tasa general de '.number_format((float) $resultado->crs_tasa_final, 4).'%.'
                : 'Tasa general '.number_format((float) $resultado->crs_tasa_general, 4).'% '
                    .($ajuste > 0 ? '+' : '−').' '.number_format(abs($ajuste), 4)
                    .' puntos = '.number_format((float) $resultado->crs_tasa_final, 4).'%.';
            if ((float) $resultado->crs_cumplimiento < (float) $periodo->cpe_cumplimiento_minimo) {
                $explicacionTasa .= ' No generó comisión porque no alcanzó el cumplimiento mínimo.';
            }

            return [(string) $resultado->crs_numero_vendedor => [
                'resumen' => [
                    'venta_bruta' => round((float) $resultado->detalles->sum('crd_venta_bruta'), 2),
                    'descuentos' => round((float) $resultado->detalles->sum('crd_descuentos'), 2),
                    'devoluciones' => round((float) $resultado->detalles->sum('crd_devoluciones'), 2),
                    'venta_neta' => (float) $resultado->crs_ventas_totales,
                    'base_comisionable' => (float) $resultado->crs_base_comisionable,
                    'ventas_sin_atencion' => (float) ($configGrupo?->cpg_ventas_sin_atencion ?? 0),
                    'vendedores_promedio' => (float) ($configGrupo?->cpg_vendedores_promedio ?? 0),
                    'incremento_meta' => (float) ($configGrupo?->cpg_incremento_meta ?? 0),
                    'cumplimiento_minimo' => (float) $periodo->cpe_cumplimiento_minimo,
                    'explicacion_tasa' => $explicacionTasa,
                ],
                'almacenes' => $porAlmacen,
                'lineas' => $porLinea,
            ]];
        })->all();

        $detalleExportacion = $resultados->flatMap(fn (ComisionResultado $resultado) => $resultado->detalles->map(fn (ComisionResultadoDetalle $detalle) => (object) [
            'no_vendedor' => $resultado->crs_numero_vendedor,
            'nombre' => $resultado->crs_nombre_vendedor,
            'grupo' => $resultado->crs_grupo_nombre,
            'almacen' => $detalle->crd_almacen_nombre,
            'linea' => $detalle->crd_linea_nombre,
            'venta_bruta' => (float) $detalle->crd_venta_bruta,
            'descuentos' => (float) $detalle->crd_descuentos,
            'devoluciones' => (float) $detalle->crd_devoluciones,
            'venta_neta' => (float) $detalle->crd_venta_neta,
        ]))->values();

        return [
            'encabezados' => $headers,
            'rows' => $rows,
            'kpis' => [
                'Estado' => str($periodo->cpe_estatus)->headline()->toString(),
                'Vendedores' => $rows->count(),
                'Ventas totales' => round($rows->sum('ventas_totales'), 2),
                'Comisiones' => round($rows->sum('comision'), 2),
                'Bonos' => round($rows->sum('bono'), 2),
                'Total pagar' => round($rows->sum('total_pagar'), 2),
            ],
            'total_registros' => $rows->count(),
            'estado_periodo' => $periodo->cpe_estatus,
            'detalles' => $detalles,
            'detalle_exportacion' => $detalleExportacion,
        ];
    }

    private function agruparDetalle(Collection $detalles, string $campo): array
    {
        return $detalles->groupBy($campo)->map(function (Collection $filas, string $nombre): array {
            return [
                'nombre' => $nombre,
                'venta_bruta' => round((float) $filas->sum('crd_venta_bruta'), 2),
                'descuentos' => round((float) $filas->sum('crd_descuentos'), 2),
                'devoluciones' => round((float) $filas->sum('crd_devoluciones'), 2),
                'venta_neta' => round((float) $filas->sum('crd_venta_neta'), 2),
            ];
        })->values()->all();
    }

    private function movimientosDetallados(ComisionPeriodo $periodo): Collection
    {
        $desde = $periodo->cpe_periodo->copy()->startOfMonth();
        $hasta = $periodo->cpe_periodo->copy()->endOfMonth();
        $almacenIds = $periodo->almacenes->pluck('alm_id');

        $totalesVenta = DB::table('tbl_pos_venta_detalle_pvd')
            ->where('pvd_deleted', false)
            ->groupBy('pvd_psv_id')
            ->selectRaw('pvd_psv_id, SUM(pvd_importe) total_detalle');

        $ventas = DB::table('tbl_pos_venta_detalle_pvd as pvd')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'pvd.pvd_psv_id')
            ->joinSub($totalesVenta, 'tot', 'tot.pvd_psv_id', '=', 'psv.psv_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pvd.pvd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_comision_periodo_lineas_cpl as cpl', 'cpl.cpl_lna_id', '=', 'prd.prd_lna_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->where('cpl.cpl_cpe_id', $periodo->cpe_id)
            ->where('psv.psv_scl_id', $periodo->cpe_scl_id)
            ->whereIn('psv.psv_alm_id', $almacenIds)
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->where('psv.psv_deleted', false)
            ->where('pvd.pvd_deleted', false)
            ->whereBetween('psv.psv_fecha_cobro', [$desde, $hasta])
            ->get([
                'pvd.pvd_usr_id as vendedor_id',
                'cpl.cpl_cgr_id as grupo_id',
                'psv.psv_alm_id as almacen_id',
                'alm.alm_nombre as almacen_nombre',
                'prd.prd_lna_id as linea_id',
                'lna.lna_nombre as linea_nombre',
                DB::raw('ROUND(pvd.pvd_importe + COALESCE(pvd.pvd_descuento_importe, 0), 2) venta_bruta'),
                DB::raw('ROUND(COALESCE(pvd.pvd_descuento_importe, 0) + (psv.psv_descuento * (1.0 * pvd.pvd_importe / NULLIF(tot.total_detalle, 0))), 2) descuentos'),
                DB::raw('0 devoluciones'),
                DB::raw('ROUND(pvd.pvd_importe - (psv.psv_descuento * (1.0 * pvd.pvd_importe / NULLIF(tot.total_detalle, 0))), 2) importe'),
            ]);

        $totalesOrigen = DB::table('tbl_pos_venta_detalle_pvd')
            ->where('pvd_deleted', false)
            ->groupBy('pvd_psv_id')
            ->selectRaw('pvd_psv_id, SUM(pvd_importe) total_detalle');

        $devoluciones = DB::table('tbl_pos_cambios_detalle_pcd as pcd')
            ->join('tbl_pos_ventas_psv as cambio', 'cambio.psv_id', '=', 'pcd.pcd_psv_id')
            ->join('tbl_pos_venta_detalle_pvd as origen_det', 'origen_det.pvd_id', '=', 'pcd.pcd_pvd_origen_id')
            ->join('tbl_pos_ventas_psv as origen', 'origen.psv_id', '=', 'origen_det.pvd_psv_id')
            ->joinSub($totalesOrigen, 'tot_origen', 'tot_origen.pvd_psv_id', '=', 'origen.psv_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pcd.pcd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_comision_periodo_lineas_cpl as cpl', 'cpl.cpl_lna_id', '=', 'prd.prd_lna_id')
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'cambio.psv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->where('cpl.cpl_cpe_id', $periodo->cpe_id)
            ->where('cambio.psv_scl_id', $periodo->cpe_scl_id)
            ->whereIn('cambio.psv_alm_id', $almacenIds)
            ->where('cambio.psv_estatus', '!=', 'cancelada')
            ->where('cambio.psv_deleted', false)
            ->where('pcd.pcd_deleted', false)
            ->where('origen_det.pvd_deleted', false)
            ->whereBetween('cambio.psv_fecha_cobro', [$desde, $hasta])
            ->get([
                'origen_det.pvd_usr_id as vendedor_id',
                'cpl.cpl_cgr_id as grupo_id',
                'cambio.psv_alm_id as almacen_id',
                'alm.alm_nombre as almacen_nombre',
                'prd.prd_lna_id as linea_id',
                'lna.lna_nombre as linea_nombre',
                DB::raw('0 venta_bruta'),
                DB::raw('0 descuentos'),
                DB::raw('ROUND(pcd.pcd_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0))), 2) devoluciones'),
                DB::raw('ROUND((pcd.pcd_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0)))) * -1, 2) importe'),
            ]);

        return $ventas->concat($devoluciones)->map(fn ($fila) => (object) [
            'vendedor_id' => $fila->vendedor_id !== null ? (int) $fila->vendedor_id : null,
            'grupo_id' => (int) $fila->grupo_id,
            'almacen_id' => (int) $fila->almacen_id,
            'almacen_nombre' => (string) $fila->almacen_nombre,
            'linea_id' => (int) $fila->linea_id,
            'linea_nombre' => (string) $fila->linea_nombre,
            'venta_bruta' => (float) $fila->venta_bruta,
            'descuentos' => (float) $fila->descuentos,
            'devoluciones' => (float) $fila->devoluciones,
            'importe' => (float) $fila->importe,
        ]);
    }
}
