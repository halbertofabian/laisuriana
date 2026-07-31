<?php

namespace App\Services\Reportes;

use App\Models\Sucursal;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReporteConsultaService
{
    public function catalogo(): array
    {
        return [
            'ventas' => ['titulo' => 'Ventas', 'descripcion' => 'Rendimiento comercial, clientes y descuentos.', 'reportes' => [
                $this->def('ventas-vendedor', 'Ventas por vendedor', 'Compara piezas, tickets, descuentos y venta neta por asesor.', 'tabler-user-chart', 'reportes.ventas.ver'),
                $this->def('ventas-producto', 'Ventas por producto y SKU', 'Detecta productos líderes, variantes con rotación y rezagos.', 'tabler-package', 'reportes.ventas.ver'),
                $this->def('ventas-categoria', 'Ventas por categoría', 'Analiza la mezcla comercial por categoría, línea y marca.', 'tabler-category-2', 'reportes.ventas.ver'),
                $this->def('ventas-cliente', 'Ventas por cliente', 'Frecuencia, ticket promedio, importe y última compra.', 'tabler-users', 'reportes.ventas.ver'),
                $this->def('ventas-metodo-pago', 'Métodos de pago', 'Conciliación de efectivo, tarjeta, mixto y monedero.', 'tabler-credit-card', 'reportes.ventas.ver'),
                $this->def('ventas-descuentos', 'Descuentos aplicados', 'Detalle de descuentos por vendedor, folio y producto.', 'tabler-discount-2', 'reportes.ventas.ver'),
                $this->def('ventas-devoluciones', 'Devoluciones', 'Productos recibidos en cambios inmediatos o mediante vales de cambio.', 'tabler-package-import', 'reportes.ventas.ver'),
            ]],
            'caja' => ['titulo' => 'Caja', 'descripcion' => 'Control de efectivo, movimientos y conciliación.', 'reportes' => [
                $this->def('caja-cortes', 'Cortes de caja', 'Resumen congelado de ventas, efectivo, gastos y retiros.', 'tabler-cash-banknote', 'reportes.caja.ver'),
                $this->def('caja-diferencias', 'Diferencias de caja', 'Sobrantes y faltantes por caja, cajero y autorización.', 'tabler-arrows-diff', 'reportes.caja.ver'),
                $this->def('caja-gastos', 'Gastos de caja', 'Gastos por categoría, referencia, motivo y responsable.', 'tabler-receipt', 'reportes.caja.ver'),
                $this->def('caja-retiros', 'Retiros de caja', 'Retiros, autorizadores y control de denominaciones.', 'tabler-cash', 'reportes.caja.ver'),
            ]],
            'inventario' => ['titulo' => 'Inventario', 'descripcion' => 'Existencias, alertas y trazabilidad de movimientos.', 'reportes' => [
                $this->def('inventario-existencias', 'Existencias por almacén', 'Disponibilidad actual por producto, SKU y almacén.', 'tabler-building-warehouse', 'reportes.inventario.ver'),
                $this->def('inventario-bajo-minimo', 'Bajo mínimo', 'Productos que requieren reposición inmediata.', 'tabler-alert-triangle', 'reportes.inventario.ver'),
                $this->def('inventario-negativos', 'Existencias negativas', 'Anomalías operativas que necesitan corrección.', 'tabler-trending-down', 'reportes.inventario.ver'),
                $this->def('inventario-movimientos', 'Movimientos y kardex', 'Entradas, salidas, ajustes, reversas y saldo resultante.', 'tabler-arrows-exchange', 'reportes.inventario.ver'),
            ]],
            'compras' => ['titulo' => 'Compras y recepción', 'descripcion' => 'Abastecimiento, proveedores y costos de entrada.', 'reportes' => [
                $this->def('compras-proveedor', 'Compras por proveedor', 'Piezas, documentos, subtotal, descuentos, IVA y total.', 'tabler-truck-delivery', 'reportes.inventario.ver'),
            ]],
        ];
    }

    public function definicion(string $slug): array
    {
        foreach ($this->catalogo() as $grupo => $categoria) foreach ($categoria['reportes'] as $reporte) if ($reporte['slug'] === $slug) return [...$reporte, 'categoria' => $grupo, 'categoria_titulo' => $categoria['titulo']];
        throw new InvalidArgumentException('El reporte solicitado no existe.');
    }

    public function consultar(string $slug, Usuario $usuario, int $sucursalId, array $filtros, bool $exportar = false): array
    {
        $def = $this->definicion($slug); [$desde, $hasta] = $this->rango($filtros);
        $resultado = match ($slug) {
            'ventas-vendedor' => $this->ventasAgrupadas('vendedor', $sucursalId, $desde, $hasta, $filtros, $exportar),
            'ventas-producto' => $this->ventasAgrupadas('producto', $sucursalId, $desde, $hasta, $filtros, $exportar),
            'ventas-categoria' => $this->ventasAgrupadas('categoria', $sucursalId, $desde, $hasta, $filtros, $exportar),
            'ventas-cliente' => $this->ventasClientes($sucursalId, $desde, $hasta, $filtros, $exportar),
            'ventas-metodo-pago' => $this->ventasMetodosPago($sucursalId, $desde, $hasta, $filtros),
            'ventas-descuentos' => $this->ventasDescuentos($sucursalId, $desde, $hasta, $filtros, $exportar),
            'ventas-devoluciones' => $this->ventasDevoluciones($sucursalId, $desde, $hasta, $filtros, $exportar),
            'caja-cortes', 'caja-diferencias' => $this->cortes($slug === 'caja-diferencias', $sucursalId, $desde, $hasta, $filtros, $exportar),
            'caja-gastos', 'caja-retiros' => $this->movimientosCaja($slug === 'caja-gastos' ? 'gasto' : 'retiro', $sucursalId, $desde, $hasta, $filtros, $exportar),
            'inventario-existencias', 'inventario-bajo-minimo', 'inventario-negativos' => $this->existencias(str_replace('inventario-', '', $slug), $sucursalId, $filtros, $exportar),
            'inventario-movimientos' => $this->movimientosInventario($sucursalId, $desde, $hasta, $filtros, $exportar),
            'compras-proveedor' => $this->comprasProveedor($sucursalId, $desde, $hasta, $filtros, $exportar),
        };
        return [...$def, 'sucursal' => Sucursal::query()->whereKey($sucursalId)->value('scl_nombre') ?? 'Sucursal activa', 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'generado_por' => $usuario->usr_nombre, ...$resultado];
    }

    private function ventasAgrupadas(string $tipo, int $sucursal, Carbon $desde, Carbon $hasta, array $f, bool $exportar): array
    {
        $mov = $this->movimientosVenta($sucursal, $desde, $hasta, $f);
        $config = [
            'vendedor' => ['keys'=>['vendedor'], 'headers'=>['Vendedor','Piezas netas','Tickets','Venta neta','Descuentos','Ticket promedio','Último movimiento']],
            'producto' => ['keys'=>['producto_codigo','producto','sku_codigo','sku','linea'], 'headers'=>['Código producto','Producto','Código SKU','Variante','Línea','Piezas netas','Tickets','Venta neta','Descuentos','Precio promedio','Último movimiento']],
            'categoria' => ['keys'=>['categoria','linea','marca'], 'headers'=>['Categoría','Línea','Marca','Piezas netas','Tickets','Venta neta','Descuentos','Participación','Último movimiento']],
        ][$tipo];
        $q = DB::query()->fromSub($mov, 'mov')->groupBy($config['keys']);
        foreach ($config['keys'] as $key) $q->addSelect($key);
        // El orden de las columnas seleccionadas debe coincidir con el de los
        // encabezados: las filas se entregan como arreglos posicionales y se
        // pintan (y exportan) en ese mismo orden.
        $q->selectRaw('ROUND(SUM(cantidad),2) as piezas_netas, COUNT(DISTINCT folio) as tickets, ROUND(SUM(importe),2) as venta_neta, ROUND(SUM(descuento),2) as descuentos');
        if ($tipo === 'vendedor') $q->selectRaw('ROUND(SUM(importe) / NULLIF(COUNT(DISTINCT folio),0),2) as ticket_promedio');
        if ($tipo === 'producto') $q->selectRaw('ROUND(SUM(importe) / NULLIF(SUM(cantidad),0),2) as precio_promedio');
        if ($tipo === 'categoria') { $total = DB::query()->fromSub(clone $mov, 't')->sum('importe'); $q->selectRaw('ROUND((SUM(importe) / NULLIF(?,0))*100,2) as participacion', [$total]); }
        $q->selectRaw('MAX(fecha) as ultimo_movimiento');
        $rows = $q->orderByDesc('venta_neta')->limit($exportar ? 20000 : 500)->get();
        return $this->resultado($config['headers'], $rows, ['Venta neta'=>$rows->sum('venta_neta'), 'Piezas netas'=>$rows->sum('piezas_netas'), 'Tickets'=>$rows->sum('tickets'), 'Descuentos'=>$rows->sum('descuentos')]);
    }

    private function movimientosVenta(int $sucursal, Carbon $desde, Carbon $hasta, array $f): Builder
    {
        $base = DB::table('tbl_pos_venta_detalle_pvd as pvd')->join('tbl_pos_ventas_psv as psv','psv.psv_id','=','pvd.pvd_psv_id')->join('tbl_producto_skus_psk as psk','psk.psk_id','=','pvd.pvd_psk_id')->join('tbl_productos_prd as prd','prd.prd_id','=','psk.psk_prd_id')
            ->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','pvd.pvd_usr_id')->leftJoin('tbl_almacenes_alm as alm','alm.alm_id','=','psv.psv_alm_id')->leftJoin('tbl_categorias_ctg as ctg','ctg.ctg_id','=','prd.prd_ctg_id')->leftJoin('tbl_lineas_lna as lna','lna.lna_id','=','prd.prd_lna_id')->leftJoin('tbl_marcas_mrc as mrc','mrc.mrc_id','=','prd.prd_mrc_id')
            ->where('psv.psv_scl_id',$sucursal)->where('psv.psv_estatus','!=','cancelada')->where('psv.psv_deleted',false)->where('pvd.pvd_deleted',false)->whereBetween('psv.psv_fecha_cobro',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])
            ->when($f['usuario_id']??null,fn($q,$v)=>$q->where('pvd.pvd_usr_id',$v))->when($f['almacen_id']??null,fn($q,$v)=>$q->where('psv.psv_alm_id',$v))->when($f['caja_id']??null,fn($q,$v)=>$q->where('psv.psv_caj_id',$v))
            // Sin vendedor en la partida, la venta se atribuye a su almacén.
            ->selectRaw("psv.psv_fecha_cobro fecha, psv.psv_folio folio, COALESCE(usr.usr_nombre,alm.alm_nombre,'Sin vendedor') vendedor, prd.prd_codigo producto_codigo, prd.prd_nombre producto, psk.psk_codigo sku_codigo, COALESCE(psk.psk_nombre,prd.prd_nombre) sku, COALESCE(ctg.ctg_nombre,'Sin categoría') categoria, COALESCE(lna.lna_nombre,'Sin línea') linea, COALESCE(mrc.mrc_nombre,'Sin marca') marca, pvd.pvd_cantidad cantidad, pvd.pvd_importe importe, pvd.pvd_descuento_importe descuento");
        $dev = DB::table('tbl_pos_cambios_detalle_pcd as pcd')->join('tbl_pos_ventas_psv as psv','psv.psv_id','=','pcd.pcd_psv_id')->join('tbl_pos_venta_detalle_pvd as pvd','pvd.pvd_id','=','pcd.pcd_pvd_origen_id')->join('tbl_producto_skus_psk as psk','psk.psk_id','=','pcd.pcd_psk_id')->join('tbl_productos_prd as prd','prd.prd_id','=','psk.psk_prd_id')
            ->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','pvd.pvd_usr_id')->leftJoin('tbl_almacenes_alm as alm','alm.alm_id','=','psv.psv_alm_id')->leftJoin('tbl_categorias_ctg as ctg','ctg.ctg_id','=','prd.prd_ctg_id')->leftJoin('tbl_lineas_lna as lna','lna.lna_id','=','prd.prd_lna_id')->leftJoin('tbl_marcas_mrc as mrc','mrc.mrc_id','=','prd.prd_mrc_id')
            ->where('psv.psv_scl_id',$sucursal)->where('psv.psv_estatus','!=','cancelada')->where('pcd.pcd_deleted',false)->whereBetween('psv.psv_fecha_cobro',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])
            ->when($f['usuario_id']??null,fn($q,$v)=>$q->where('pvd.pvd_usr_id',$v))->when($f['almacen_id']??null,fn($q,$v)=>$q->where('psv.psv_alm_id',$v))->when($f['caja_id']??null,fn($q,$v)=>$q->where('psv.psv_caj_id',$v))
            ->selectRaw("psv.psv_fecha_cobro fecha, psv.psv_folio folio, COALESCE(usr.usr_nombre,alm.alm_nombre,'Sin vendedor') vendedor, prd.prd_codigo producto_codigo, prd.prd_nombre producto, psk.psk_codigo sku_codigo, COALESCE(psk.psk_nombre,prd.prd_nombre) sku, COALESCE(ctg.ctg_nombre,'Sin categoría') categoria, COALESCE(lna.lna_nombre,'Sin línea') linea, COALESCE(mrc.mrc_nombre,'Sin marca') marca, pcd.pcd_cantidad * -1 cantidad, pcd.pcd_importe_credito * -1 importe, ((pcd.pcd_cantidad * pcd.pcd_precio_unitario)-pcd.pcd_importe_credito) * -1 descuento");
        return $base->unionAll($dev);
    }

    private function ventasClientes(int $sucursal, Carbon $desde, Carbon $hasta, array $f, bool $exportar): array
    {
        $rows = DB::table('tbl_pos_ventas_psv as psv')->leftJoin('tbl_clientes_cli as cli','cli.cli_id','=','psv.psv_cli_id')->where('psv.psv_scl_id',$sucursal)->where('psv.psv_estatus','!=','cancelada')->where('psv.psv_deleted',false)->whereBetween('psv.psv_fecha_cobro',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])
            ->when($f['caja_id']??null,fn($q,$v)=>$q->where('psv.psv_caj_id',$v))->when($f['q']??null,fn($q,$v)=>$q->where(fn($s)=>$s->where('cli.cli_nombre','like',"%{$v}%")->orWhere('cli.cli_telefono','like',"%{$v}%")))
            ->groupBy('psv.psv_cli_id','cli.cli_nombre','cli.cli_apellido_paterno','cli.cli_telefono','cli.cli_ciudad')->selectRaw("COALESCE(TRIM(CONCAT(cli.cli_nombre,' ',COALESCE(cli.cli_apellido_paterno,''))),'Público general') cliente, COALESCE(cli.cli_telefono,'—') telefono, COALESCE(cli.cli_ciudad,'—') ciudad, COUNT(*) tickets, ROUND(SUM(psv.psv_total),2) total, ROUND(AVG(psv.psv_total),2) ticket_promedio, MAX(psv.psv_fecha_cobro) ultima_compra")->orderByDesc('total')->limit($exportar?20000:500)->get();
        return $this->resultado(['Cliente','Teléfono','Ciudad','Tickets','Venta total','Ticket promedio','Última compra'],$rows,['Clientes'=>$rows->count(),'Tickets'=>$rows->sum('tickets'),'Venta total'=>$rows->sum('total'),'Ticket promedio'=>$rows->avg('ticket_promedio')]);
    }

    private function ventasMetodosPago(int $sucursal, Carbon $desde, Carbon $hasta, array $f): array
    {
        $ventas = DB::table('tbl_pos_ventas_psv')->where('psv_scl_id',$sucursal)->where('psv_estatus','!=','cancelada')->where('psv_deleted',false)->whereBetween('psv_fecha_cobro',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($f['caja_id']??null,fn($q,$v)=>$q->where('psv_caj_id',$v))->get(['psv_metodo_pago','psv_pago_detalle','psv_total']);
        $acum=[]; foreach($ventas as $v){ $detalle=json_decode((string)$v->psv_pago_detalle,true)?:[]; if($v->psv_metodo_pago==='mixto'&&$detalle){ foreach(['efectivo','tarjeta','monedero_electronico'] as $m){$monto=(float)($detalle[$m]??0);if($monto>0){$acum[$m]['monto']=($acum[$m]['monto']??0)+$monto;$acum[$m]['tickets']=($acum[$m]['tickets']??0)+1;}}}else{$m=$v->psv_metodo_pago?:'sin_especificar';$acum[$m]['monto']=($acum[$m]['monto']??0)+(float)$v->psv_total;$acum[$m]['tickets']=($acum[$m]['tickets']??0)+1;}}
        $total=array_sum(array_column($acum,'monto')); $rows=collect($acum)->map(fn($x,$m)=>(object)['metodo'=>str($m)->replace('_',' ')->title(),'tickets'=>$x['tickets'],'monto'=>round($x['monto'],2),'participacion'=>$total?round($x['monto']/$total*100,2):0])->sortByDesc('monto')->values();
        return $this->resultado(['Método','Tickets involucrados','Monto','Participación %'],$rows,['Total cobrado'=>$total,'Tickets'=>$ventas->count(),'Métodos'=>$rows->count(),'Efectivo'=>$acum['efectivo']['monto']??0]);
    }

    private function ventasDescuentos(int $sucursal, Carbon $desde, Carbon $hasta, array $f, bool $exportar): array
    {
        $rows=DB::table('tbl_pos_venta_detalle_pvd as pvd')->join('tbl_pos_ventas_psv as psv','psv.psv_id','=','pvd.pvd_psv_id')->join('tbl_producto_skus_psk as psk','psk.psk_id','=','pvd.pvd_psk_id')->join('tbl_productos_prd as prd','prd.prd_id','=','psk.psk_prd_id')->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','pvd.pvd_usr_id')->where('psv.psv_scl_id',$sucursal)->where('psv.psv_estatus','!=','cancelada')->where('pvd.pvd_descuento_importe','>',0)->whereBetween('psv.psv_fecha_cobro',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($f['usuario_id']??null,fn($q,$v)=>$q->where('pvd.pvd_usr_id',$v))->orderByDesc('psv.psv_fecha_cobro')->limit($exportar?20000:500)->get(['psv.psv_fecha_cobro as fecha','psv.psv_folio as folio','usr.usr_nombre as vendedor','prd.prd_nombre as producto','psk.psk_codigo as sku','pvd.pvd_cantidad as piezas','pvd.pvd_descuento_porcentaje as porcentaje','pvd.pvd_descuento_importe as descuento','pvd.pvd_importe as importe_neto']);
        return $this->resultado(['Fecha','Folio','Vendedor','Producto','SKU','Piezas','Descuento %','Descuento $','Importe neto'],$rows,['Descuento total'=>$rows->sum('descuento'),'Operaciones'=>$rows->count(),'Venta neta'=>$rows->sum('importe_neto'),'Descuento promedio %'=>$rows->avg('porcentaje')]);
    }

    private function ventasDevoluciones(int $sucursal, Carbon $desde, Carbon $hasta, array $f, bool $exportar): array
    {
        $cambios = DB::table('tbl_pos_cambios_detalle_pcd as pcd')
            ->join('tbl_pos_ventas_psv as cambio', 'cambio.psv_id', '=', 'pcd.pcd_psv_id')
            ->join('tbl_pos_ventas_psv as origen', 'origen.psv_id', '=', 'pcd.pcd_psv_origen_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pcd.pcd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'cambio.psv_usr_id')
            ->where('cambio.psv_scl_id', $sucursal)->where('pcd.pcd_deleted', false)->where('cambio.psv_estatus', '!=', 'cancelada')
            ->whereBetween('cambio.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($f['caja_id'] ?? null, fn ($q, $v) => $q->where('cambio.psv_caj_id', $v))
            ->when($f['almacen_id'] ?? null, fn ($q, $v) => $q->where('pcd.pcd_alm_id', $v))
            ->selectRaw("cambio.psv_fecha_cobro fecha, 'Cambio inmediato' tipo, cambio.psv_folio folio, origen.psv_folio venta_origen, COALESCE(usr.usr_nombre,'Sin responsable') responsable, prd.prd_nombre producto, psk.psk_codigo sku, pcd.pcd_cantidad cantidad, pcd.pcd_condicion condicion, pcd.pcd_importe_credito credito, cambio.psv_estatus estado");

        $vales = DB::table('tbl_pos_creditos_cambio_detalle_pcdv as pcdv')
            ->join('tbl_pos_creditos_cambio_pcc as pcc', 'pcc.pcc_id', '=', 'pcdv.pcdv_pcc_id')
            ->join('tbl_pos_ventas_psv as origen', 'origen.psv_id', '=', 'pcdv.pcdv_psv_origen_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pcdv.pcdv_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pcc.pcc_usr_id')
            ->where('pcc.pcc_scl_id', $sucursal)->where('pcdv.pcdv_deleted', false)->where('pcc.pcc_deleted', false)
            ->whereBetween('pcc.pcc_fecha_generado', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($f['caja_id'] ?? null, fn ($q, $v) => $q->where('pcc.pcc_caj_id', $v))
            ->when($f['almacen_id'] ?? null, fn ($q, $v) => $q->where('pcdv.pcdv_alm_id', $v))
            ->selectRaw("pcc.pcc_fecha_generado fecha, 'Vale de cambio' tipo, pcc.pcc_folio folio, origen.psv_folio venta_origen, COALESCE(usr.usr_nombre,'Sin responsable') responsable, prd.prd_nombre producto, psk.psk_codigo sku, pcdv.pcdv_cantidad cantidad, pcdv.pcdv_condicion condicion, pcdv.pcdv_importe_credito credito, pcc.pcc_estatus estado");

        $rows = DB::query()->fromSub($cambios->unionAll($vales), 'devoluciones')->orderByDesc('fecha')->limit($exportar ? 20000 : 500)->get();

        return $this->resultado(
            ['Fecha', 'Tipo', 'Folio devolución/vale', 'Venta origen', 'Responsable', 'Producto', 'SKU', 'Cantidad', 'Condición', 'Crédito reconocido', 'Estado'],
            $rows,
            ['Devoluciones' => $rows->pluck('folio')->unique()->count(), 'Piezas recibidas' => $rows->sum('cantidad'), 'Crédito reconocido' => $rows->sum('credito'), 'En revisión' => $rows->where('condicion', 'revision')->sum('cantidad')]
        );
    }

    private function cortes(bool $soloDiferencias,int $sucursal,Carbon $desde,Carbon $hasta,array $f,bool $exportar): array
    {
        $rows=DB::table('tbl_pos_cortes_pco as pco')->leftJoin('tbl_cajas_caj as caj','caj.caj_id','=','pco.pco_caj_id')->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','pco.pco_usr_cajero_id')->leftJoin('tbl_usuarios_usr as aut','aut.usr_id','=','pco.pco_usr_autorizo_id')->where('pco.pco_scl_id',$sucursal)->where('pco.pco_deleted',false)->whereBetween('pco.pco_cerrada_at',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($soloDiferencias,fn($q)=>$q->where('pco.pco_diferencia','!=',0))->when($f['caja_id']??null,fn($q,$v)=>$q->where('pco.pco_caj_id',$v))->orderByDesc('pco.pco_cerrada_at')->limit($exportar?20000:500)->get(['pco.pco_folio as folio','pco.pco_cerrada_at as cierre','caj.caj_nombre as caja','usr.usr_nombre as cajero','aut.usr_nombre as autorizó','pco.pco_total_ventas as ventas','pco.pco_total_gastos as gastos','pco.pco_total_retiros as retiros','pco.pco_efectivo_esperado as esperado','pco.pco_efectivo_reportado as reportado','pco.pco_diferencia as diferencia']);
        return $this->resultado(['Folio','Cierre','Caja','Cajero','Autorizó','Ventas','Gastos','Retiros','Esperado','Reportado','Diferencia'],$rows,['Cortes'=>$rows->count(),'Ventas'=>$rows->sum('ventas'),'Diferencia neta'=>$rows->sum('diferencia'),'Con diferencia'=>$rows->filter(fn($r)=>(float)$r->diferencia!==0.0)->count()]);
    }

    private function movimientosCaja(string $tipo,int $sucursal,Carbon $desde,Carbon $hasta,array $f,bool $exportar): array
    {
        $rows=DB::table('tbl_caja_movimientos_cjm as cjm')->leftJoin('tbl_cajas_caj as caj','caj.caj_id','=','cjm.cjm_caj_id')->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','cjm.cjm_usr_cajero_id')->leftJoin('tbl_usuarios_usr as aut','aut.usr_id','=','cjm.cjm_usr_autorizo_id')->where('cjm.cjm_scl_id',$sucursal)->where('cjm.cjm_tipo',$tipo)->where('cjm.cjm_estatus','registrado')->where('cjm.cjm_deleted',false)->whereBetween('cjm.cjm_fecha_movimiento',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($f['caja_id']??null,fn($q,$v)=>$q->where('cjm.cjm_caj_id',$v))->orderByDesc('cjm.cjm_fecha_movimiento')->limit($exportar?20000:500)->get(['cjm.cjm_fecha_movimiento as fecha','cjm.cjm_folio as folio','caj.caj_nombre as caja','usr.usr_nombre as cajero','aut.usr_nombre as autorizó','cjm.cjm_categoria as categoria','cjm.cjm_referencia as referencia','cjm.cjm_motivo as motivo','cjm.cjm_monto as monto']);
        return $this->resultado(['Fecha','Folio','Caja','Cajero','Autorizó','Categoría','Referencia','Motivo','Monto'],$rows,['Monto total'=>$rows->sum('monto'),'Movimientos'=>$rows->count(),'Promedio'=>$rows->avg('monto'),'Cajas'=>$rows->pluck('caja')->unique()->count()]);
    }

    private function existencias(string $tipo,int $sucursal,array $f,bool $exportar): array
    {
        $q=DB::table('tbl_existencias_almacen_exa as exa')->join('tbl_producto_skus_psk as psk','psk.psk_id','=','exa.exa_psk_id')->join('tbl_productos_prd as prd','prd.prd_id','=','psk.psk_prd_id')->join('tbl_almacenes_alm as alm','alm.alm_id','=','exa.exa_alm_id')->leftJoin('tbl_categorias_ctg as ctg','ctg.ctg_id','=','prd.prd_ctg_id')->leftJoin('tbl_minimos_inventario_mni as mni',fn($j)=>$j->on('mni.mni_psk_id','=','exa.exa_psk_id')->on('mni.mni_alm_id','=','exa.exa_alm_id')->on('mni.mni_scl_id','=','exa.exa_scl_id')->where('mni.mni_deleted',false))->where('exa.exa_scl_id',$sucursal)->where('exa.exa_deleted',false)->when($f['almacen_id']??null,fn($x,$v)=>$x->where('exa.exa_alm_id',$v))->when($tipo==='negativos',fn($x)=>$x->where('exa.exa_existencia','<',0))->when($tipo==='bajo-minimo',fn($x)=>$x->whereNotNull('mni.mni_minimo')->whereColumn('exa.exa_existencia','<=','mni.mni_minimo'));
        $rows=$q->orderBy('prd.prd_nombre')->limit($exportar?20000:1000)->get(['psk.psk_codigo as sku','prd.prd_nombre as producto','psk.psk_nombre as variante','ctg.ctg_nombre as categoria','alm.alm_nombre as almacen','exa.exa_existencia as existencia','mni.mni_minimo as minimo','psk.psk_costo as costo_actual',DB::raw('ROUND(exa.exa_existencia * psk.psk_costo,2) as valor_referencial')]);
        return $this->resultado(['SKU','Producto','Variante','Categoría','Almacén','Existencia','Mínimo','Costo actual','Valor referencial'],$rows,['SKUs'=>$rows->count(),'Piezas'=>$rows->sum('existencia'),'Bajo mínimo'=>$rows->filter(fn($r)=>$r->minimo!==null&&(float)$r->existencia<=(float)$r->minimo)->count(),'Valor referencial'=>$rows->sum('valor_referencial')]);
    }

    private function movimientosInventario(int $sucursal,Carbon $desde,Carbon $hasta,array $f,bool $exportar): array
    {
        $rows=DB::table('tbl_movimientos_inventario_min as min')->join('tbl_tipos_movimiento_inventario_tmi as tmi','tmi.tmi_id','=','min.min_tmi_id')->join('tbl_producto_skus_psk as psk','psk.psk_id','=','min.min_psk_id')->join('tbl_productos_prd as prd','prd.prd_id','=','psk.psk_prd_id')->join('tbl_almacenes_alm as alm','alm.alm_id','=','min.min_alm_id')->leftJoin('tbl_usuarios_usr as usr','usr.usr_id','=','min.min_created_by_usr_id')->where('min.min_scl_id',$sucursal)->where('min.min_deleted',false)->whereBetween('min.min_fecha_movimiento',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($f['almacen_id']??null,fn($q,$v)=>$q->where('min.min_alm_id',$v))->orderByDesc('min.min_fecha_movimiento')->limit($exportar?20000:1000)->get(['min.min_fecha_movimiento as fecha','min.min_folio as folio','tmi.tmi_nombre as tipo','min.min_documento_referencia as referencia','psk.psk_codigo as sku','prd.prd_nombre as producto','alm.alm_nombre as almacen','usr.usr_nombre as usuario',DB::raw('min.min_cantidad * min.min_signo as cantidad_neta'),'min.min_existencia_antes as saldo_anterior','min.min_existencia_despues as saldo_resultante','min.min_estatus as estatus']);
        return $this->resultado(['Fecha','Folio','Tipo','Referencia','SKU','Producto','Almacén','Usuario','Cantidad neta','Saldo anterior','Saldo resultante','Estatus'],$rows,['Movimientos'=>$rows->count(),'Entradas'=>$rows->filter(fn($r)=>(float)$r->cantidad_neta>0)->sum('cantidad_neta'),'Salidas'=>abs($rows->filter(fn($r)=>(float)$r->cantidad_neta<0)->sum('cantidad_neta')),'SKUs afectados'=>$rows->pluck('sku')->unique()->count()]);
    }

    private function comprasProveedor(int $sucursal,Carbon $desde,Carbon $hasta,array $f,bool $exportar): array
    {
        $rows=DB::table('tbl_movimientos_inventario_min as min')->leftJoin('tbl_proveedores_prv as prv','prv.prv_id','=','min.min_prv_id')->where('min.min_scl_id',$sucursal)->whereNotNull('min.min_prv_id')->where('min.min_deleted',false)->where('min.min_estatus','activo')->whereBetween('min.min_fecha_movimiento',[$desde->copy()->startOfDay(),$hasta->copy()->endOfDay()])->when($f['almacen_id']??null,fn($q,$v)=>$q->where('min.min_alm_id',$v))->groupBy('min.min_prv_id','prv.prv_clave','prv.prv_nombre_empresa')->selectRaw("prv.prv_clave clave, prv.prv_nombre_empresa proveedor, COUNT(DISTINCT min.min_rme_id) recepciones, COUNT(DISTINCT min.min_documento_referencia) documentos, ROUND(SUM(min.min_cantidad),2) piezas, ROUND(SUM(COALESCE(min.min_subtotal_linea,0)),2) subtotal, ROUND(SUM(COALESCE(min.min_descuento_linea,0)),2) descuentos, ROUND(SUM(COALESCE(min.min_iva_linea,0)),2) iva, ROUND(SUM(COALESCE(min.min_total_linea,0)),2) total, MAX(min.min_fecha_movimiento) ultima_recepcion")->orderByDesc('total')->limit($exportar?20000:500)->get();
        return $this->resultado(['Clave','Proveedor','Recepciones','Documentos','Piezas','Subtotal','Descuentos','IVA','Total','Última recepción'],$rows,['Proveedores'=>$rows->count(),'Recepciones'=>$rows->sum('recepciones'),'Piezas'=>$rows->sum('piezas'),'Total comprado'=>$rows->sum('total')]);
    }

    private function resultado(array $headers, Collection $rows, array $kpis): array { return ['encabezados'=>$headers,'rows'=>$rows->values(),'kpis'=>collect($kpis)->map(fn($v)=>is_numeric($v)?round((float)$v,2):$v)->all(),'total_registros'=>$rows->count()]; }
    private function rango(array $f): array { $desde=Carbon::parse($f['desde']??now()->toDateString());$hasta=Carbon::parse($f['hasta']??now()->toDateString());if($desde->diffInDays($hasta)>366)throw new InvalidArgumentException('El rango máximo permitido es de 366 días.');return[$desde,$hasta]; }
    private function def(string $slug,string $titulo,string $descripcion,string $icono,string $permiso): array { return compact('slug','titulo','descripcion','icono','permiso'); }
}
