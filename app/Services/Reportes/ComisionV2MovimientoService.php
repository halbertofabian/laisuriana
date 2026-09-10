<?php

namespace App\Services\Reportes;

use App\Models\ComisionV2Periodo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComisionV2MovimientoService
{
    public function obtener(ComisionV2Periodo $periodo, Carbon $desde, Carbon $hasta): Collection
    {
        $almacenIds = $periodo->almacenes->pluck('alm_id');
        if ($almacenIds->isEmpty()) {
            return collect();
        }

        $totalesVenta = DB::table('tbl_pos_venta_detalle_pvd')
            ->where('pvd_deleted', false)
            ->groupBy('pvd_psv_id')
            ->selectRaw('pvd_psv_id, SUM(pvd_importe) total_detalle');

        $ventas = DB::table('tbl_pos_venta_detalle_pvd as pvd')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'pvd.pvd_psv_id')
            ->joinSub($totalesVenta, 'tot', 'tot.pvd_psv_id', '=', 'psv.psv_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pvd.pvd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_comision_v2_periodo_lineas_cml as cml', function ($join) use ($periodo) {
                $join->on('cml.cml_lna_id', '=', 'prd.prd_lna_id')
                    ->where('cml.cml_cmp_id', '=', $periodo->cmp_id);
            })
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->where('psv.psv_scl_id', $periodo->cmp_scl_id)
            ->whereIn('psv.psv_alm_id', $almacenIds)
            ->where('psv.psv_tipo_operacion', 'venta')
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->where('psv.psv_deleted', false)
            ->where('pvd.pvd_deleted', false)
            ->whereBetween('psv.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->get([
                'pvd.pvd_usr_id as vendedor_id',
                'cml.cml_cpd_id as departamento_periodo_id',
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
            ->join('tbl_comision_v2_periodo_lineas_cml as cml', function ($join) use ($periodo) {
                $join->on('cml.cml_lna_id', '=', 'prd.prd_lna_id')
                    ->where('cml.cml_cmp_id', '=', $periodo->cmp_id);
            })
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'origen.psv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->where('cambio.psv_scl_id', $periodo->cmp_scl_id)
            ->whereIn('origen.psv_alm_id', $almacenIds)
            ->where('cambio.psv_estatus', '!=', 'cancelada')
            ->where('cambio.psv_deleted', false)
            ->where('pcd.pcd_deleted', false)
            ->where('origen_det.pvd_deleted', false)
            ->whereBetween('cambio.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->get([
                'origen_det.pvd_usr_id as vendedor_id',
                'cml.cml_cpd_id as departamento_periodo_id',
                'origen.psv_alm_id as almacen_id',
                'alm.alm_nombre as almacen_nombre',
                'prd.prd_lna_id as linea_id',
                'lna.lna_nombre as linea_nombre',
                DB::raw('0 venta_bruta'),
                DB::raw('0 descuentos'),
                DB::raw('ROUND(pcd.pcd_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0))), 2) devoluciones'),
                DB::raw('ROUND((pcd.pcd_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0)))) * -1, 2) importe'),
            ]);

        $vales = DB::table('tbl_pos_creditos_cambio_detalle_pcdv as pcdv')
            ->join('tbl_pos_creditos_cambio_pcc as pcc', 'pcc.pcc_id', '=', 'pcdv.pcdv_pcc_id')
            ->join('tbl_pos_venta_detalle_pvd as origen_det', 'origen_det.pvd_id', '=', 'pcdv.pcdv_pvd_origen_id')
            ->join('tbl_pos_ventas_psv as origen', 'origen.psv_id', '=', 'origen_det.pvd_psv_id')
            ->joinSub($totalesOrigen, 'tot_origen', 'tot_origen.pvd_psv_id', '=', 'origen.psv_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pcdv.pcdv_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->join('tbl_comision_v2_periodo_lineas_cml as cml', function ($join) use ($periodo) {
                $join->on('cml.cml_lna_id', '=', 'prd.prd_lna_id')
                    ->where('cml.cml_cmp_id', '=', $periodo->cmp_id);
            })
            ->join('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'origen.psv_alm_id')
            ->join('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->where('pcc.pcc_scl_id', $periodo->cmp_scl_id)
            ->whereIn('origen.psv_alm_id', $almacenIds)
            ->where('pcc.pcc_estatus', '!=', 'cancelado')
            ->where('pcc.pcc_deleted', false)
            ->where('pcdv.pcdv_deleted', false)
            ->where('origen_det.pvd_deleted', false)
            ->whereBetween('pcc.pcc_fecha_generado', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->get([
                'origen_det.pvd_usr_id as vendedor_id',
                'cml.cml_cpd_id as departamento_periodo_id',
                'origen.psv_alm_id as almacen_id',
                'alm.alm_nombre as almacen_nombre',
                'prd.prd_lna_id as linea_id',
                'lna.lna_nombre as linea_nombre',
                DB::raw('0 venta_bruta'),
                DB::raw('0 descuentos'),
                DB::raw('ROUND(pcdv.pcdv_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0))), 2) devoluciones'),
                DB::raw('ROUND((pcdv.pcdv_importe_credito * (1 - (1.0 * origen.psv_descuento / NULLIF(tot_origen.total_detalle, 0)))) * -1, 2) importe'),
            ]);

        return $ventas->concat($devoluciones)->concat($vales)->map(fn ($fila) => (object) [
            'vendedor_id' => $fila->vendedor_id !== null ? (int) $fila->vendedor_id : null,
            'departamento_periodo_id' => (int) $fila->departamento_periodo_id,
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
