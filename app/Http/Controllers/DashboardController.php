<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function desktop()
    {
        return view('desktop.dashboard');
    }

    public function desktopUsuarios()
    {
        return view('desktop.usuarios', [
            'opciones' => [
                'roles' => Rol::query()
                    ->where('rol_estatus', 'activo')
                    ->orderBy('rol_nombre')
                    ->get(['rol_id', 'rol_nombre']),
                'sucursales' => Sucursal::query()
                    ->where('scl_estatus', 'activo')
                    ->orderBy('scl_nombre')
                    ->get(['scl_id', 'scl_nombre']),
            ],
        ]);
    }

    public function desktopRoles()
    {
        return view('desktop.roles', [
            'permisos' => Permiso::query()
                ->orderBy('prm_modulo')
                ->orderBy('prm_clave')
                ->get(),
        ]);
    }

    public function desktopPermisos()
    {
        return view('desktop.permisos');
    }

    public function desktopBitacora()
    {
        return view('desktop.bitacora');
    }

    public function desktopReportes()
    {
        return view('desktop.reportes', [
            'opciones' => [
                'vendedores' => Usuario::query()
                    ->where('usr_estatus', 'activo')
                    ->where('usr_deleted', false)
                    ->whereNull('usr_deleted_at')
                    ->orderBy('usr_nombre')
                    ->get(['usr_id', 'usr_nombre']),
                'lineas' => Linea::query()
                    ->where('lna_estatus', 'activo')
                    ->where('lna_deleted', false)
                    ->whereNull('lna_deleted_at')
                    ->orderBy('lna_nombre')
                    ->get(['lna_id', 'lna_nombre']),
            ],
        ]);
    }

    public function desktopReportesVentasVendedoresData(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'tiempo' => ['nullable', 'string', 'in:hoy,ayer,antier,semana,mes,rango'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string', 'in:todos,venta,cambio_devolucion,cambio_nuevo'],
            'vendedor_id' => ['nullable', 'integer'],
            'linea_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $reporte = $this->obtenerReporteVentasVendedores($filtros);

        return response()->json([
            'data' => $reporte['rows'],
            'meta' => [
                'desde' => $reporte['desde']->toDateString(),
                'hasta' => $reporte['hasta']->toDateString(),
            ],
        ]);
    }

    public function desktopReportesVentasVendedoresExportarExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtros = $request->validate([
            'tiempo' => ['nullable', 'string', 'in:hoy,ayer,antier,semana,mes,rango'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string', 'in:todos,venta,cambio_devolucion,cambio_nuevo'],
            'vendedor_id' => ['nullable', 'integer'],
            'linea_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $reporte = $this->obtenerReporteVentasVendedores($filtros);
        $fileName = 'reporte-ventas-vendedores-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($reporte) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Tipo',
                'Folio',
                'Referencia',
                'Vendedor',
                'Linea',
                'Producto',
                'Codigo',
                'Piezas',
                'Importe',
                'Descuento',
                'Precio unitario',
                'Ultima venta',
            ]);

            foreach ($reporte['rows'] as $row) {
                fputcsv($handle, [
                    $row['tipo'],
                    $row['folio'],
                    $row['referencia'] ?? '',
                    $row['vendedor'],
                    $row['linea'],
                    $row['producto'],
                    $row['codigo'],
                    $row['piezas_vendidas'],
                    $row['importe_total'],
                    $row['descuento_total'],
                    $row['precio_unitario'],
                    $row['ultima_venta'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function desktopReportesVentasVendedoresExportarPdf(Request $request): \Illuminate\Http\Response
    {
        $filtros = $request->validate([
            'tiempo' => ['nullable', 'string', 'in:hoy,ayer,antier,semana,mes,rango'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string', 'in:todos,venta,cambio_devolucion,cambio_nuevo'],
            'vendedor_id' => ['nullable', 'integer'],
            'linea_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $reporte = $this->obtenerReporteVentasVendedores($filtros);
        $fechaGen = now()->format('d/m/Y H:i:s');
        $rowsHtml = '';
        $rowIdx = 0;

        foreach ($reporte['rows'] as $row) {
            $bg = ($rowIdx % 2 === 0) ? '#f8fafc' : '#ffffff';
            $rowIdx++;

            $rowsHtml .= '<tr style="background:' . $bg . '">'
                . '<td>' . $this->escapePdfText($row['tipo']) . '</td>'
                . '<td>' . $this->escapePdfText($row['folio']) . '<br><span style="color:#64748b;">Ref. ' . $this->escapePdfText($row['referencia'] ?? '-') . '</span></td>'
                . '<td>' . $this->escapePdfText($row['vendedor']) . '</td>'
                . '<td>' . $this->escapePdfText($row['linea']) . '</td>'
                . '<td>' . $this->escapePdfText($row['producto']) . '</td>'
                . '<td>' . $this->escapePdfText($row['codigo']) . '</td>'
                . '<td style="text-align:right">' . number_format((float) $row['piezas_vendidas'], 2) . '</td>'
                . '<td style="text-align:right">$' . number_format((float) $row['importe_total'], 2) . '</td>'
                . '<td style="text-align:right">$' . number_format((float) $row['descuento_total'], 2) . '</td>'
                . '<td style="text-align:right">$' . number_format((float) $row['precio_unitario'], 2) . '</td>'
                . '<td>' . $this->escapePdfText($row['ultima_venta'] ?? '-') . '</td>'
                . '</tr>';
        }

        $html = '
        <style>
            body { font-family: helvetica; font-size: 7.5px; color: #1e293b; }
            h2 { font-size: 12px; color: #1e3a5f; margin: 0 0 2px 0; }
            .meta { font-size: 7px; color: #64748b; margin-bottom: 6px; }
            table { border-collapse: collapse; width: 100%; }
            th { background: #1e3a5f; color: #fff; font-size: 7px; padding: 4px 3px; text-align: left; }
            td { font-size: 6.8px; padding: 3px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        </style>
        <h2>Reporte de ventas por vendedor</h2>
        <div class="meta">Rango: ' . $reporte['desde']->format('Y-m-d') . ' a ' . $reporte['hasta']->format('Y-m-d') . ' &bull; '
            . $reporte['rows']->count() . ' registros &bull; Generado: ' . $fechaGen . '</div>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Folio / Ref.</th>
                    <th>Vendedor</th>
                    <th>Linea</th>
                    <th>Producto</th>
                    <th>Codigo</th>
                    <th style="text-align:right">Piezas</th>
                    <th style="text-align:right">Importe</th>
                    <th style="text-align:right">Descuento</th>
                    <th style="text-align:right">Precio unit.</th>
                    <th>Ultima venta</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
        </table>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) (request()->user()?->name ?? config('app.name')));
        $pdf->SetTitle('Reporte de ventas por vendedor');
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('reporte-ventas-vendedores.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reporte-ventas-vendedores.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function obtenerReporteVentasVendedores(array $filtros): array
    {
        [$desde, $hasta] = $this->resolverRangoReporte($filtros);
        $tipo = (string) ($filtros['tipo'] ?? 'todos');
        $rows = collect();

        if (in_array($tipo, ['todos', 'venta'], true)) {
            $rows = $rows->concat($this->reporteVentasVendedoresVentas($filtros, $desde, $hasta));
        }
        if (in_array($tipo, ['todos', 'cambio_nuevo'], true)) {
            $rows = $rows->concat($this->reporteVentasVendedoresCambioNuevos($filtros, $desde, $hasta));
        }
        if (in_array($tipo, ['todos', 'cambio_devolucion'], true)) {
            $rows = $rows->concat($this->reporteVentasVendedoresCambios($filtros, $desde, $hasta));
        }

        $rows = $rows->sortBy([
            ['movimiento_fecha_sort', 'desc'],
            ['vendedor', 'asc'],
            ['producto', 'asc'],
        ])->values()->map(function (array $row) {
            unset($row['movimiento_fecha_sort']);
            return $row;
        });

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'rows' => $rows,
        ];
    }

    private function escapePdfText(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8');
    }

    private function reporteVentasVendedoresVentas(array $filtros, Carbon $desde, Carbon $hasta): Collection
    {
        $buscar = trim((string) ($filtros['q'] ?? ''));

        return DB::table('tbl_pos_venta_detalle_pvd as pvd')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'pvd.pvd_psv_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pvd.pvd_usr_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pvd.pvd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->where('pvd.pvd_deleted', false)
            ->whereNull('pvd.pvd_deleted_at')
            ->where('psv.psv_deleted', false)
            ->whereNull('psv.psv_deleted_at')
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->where('psv.psv_tipo_operacion', 'venta')
            ->whereBetween('psv.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when(!empty($filtros['vendedor_id']), function ($query) use ($filtros): void {
                $query->where('pvd.pvd_usr_id', (int) $filtros['vendedor_id']);
            })
            ->when(!empty($filtros['linea_id']), function ($query) use ($filtros): void {
                $query->where('prd.prd_lna_id', (int) $filtros['linea_id']);
            })
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('usr.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('alm.alm_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('lna.lna_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psv.psv_folio', 'like', "%{$buscar}%");
                });
            })
            ->groupBy(
                'psv.psv_id',
                'psv.psv_fecha_cobro',
                'psv.psv_folio',
                'usr.usr_nombre',
                'alm.alm_nombre',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_codigo',
                'prd.prd_nombre',
                'lna.lna_nombre',
                'pvd.pvd_precio_unitario',
                'pvd.pvd_descuento_porcentaje'
            )
            ->selectRaw('
                "Venta" as movimiento_tipo,
                psv.psv_fecha_cobro as movimiento_fecha,
                psv.psv_folio as folio,
                NULL as referencia_folio,
                COALESCE(NULLIF(usr.usr_nombre, ""), alm.alm_nombre, "Sin vendedor") as vendedor_nombre,
                psk.psk_codigo as sku_codigo,
                psk.psk_nombre as sku_nombre,
                prd.prd_codigo as producto_codigo,
                prd.prd_nombre as producto_nombre,
                COALESCE(lna.lna_nombre, "Sin línea") as linea_nombre,
                ROUND(SUM(pvd.pvd_cantidad), 2) as piezas_vendidas,
                COUNT(DISTINCT psv.psv_id) as tickets,
                ROUND(SUM(pvd.pvd_importe), 2) as importe_total,
                ROUND(SUM(COALESCE(pvd.pvd_descuento_importe, 0)), 2) as descuento_total,
                MAX(COALESCE(pvd.pvd_descuento_porcentaje, 0)) as descuento_porcentaje_max,
                ROUND(MAX(pvd.pvd_precio_unitario), 2) as precio_unitario
            ')
            ->get()
            ->map(fn ($row) => $this->mapReporteVentaVendedorRow($row))
            ->values();
    }

    private function reporteVentasVendedoresCambios(array $filtros, Carbon $desde, Carbon $hasta): Collection
    {
        $buscar = trim((string) ($filtros['q'] ?? ''));

        return DB::table('tbl_pos_cambios_detalle_pcd as pcd')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'pcd.pcd_psv_id')
            ->join('tbl_pos_venta_detalle_pvd as pvd', 'pvd.pvd_id', '=', 'pcd.pcd_pvd_origen_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pvd.pvd_usr_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pcd.pcd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->where('pcd.pcd_deleted', false)
            ->whereNull('pcd.pcd_deleted_at')
            ->where('psv.psv_deleted', false)
            ->whereNull('psv.psv_deleted_at')
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->where('psv.psv_tipo_operacion', 'cambio')
            ->where('pvd.pvd_deleted', false)
            ->whereNull('pvd.pvd_deleted_at')
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->whereBetween('psv.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when(!empty($filtros['vendedor_id']), function ($query) use ($filtros): void {
                $query->where('pvd.pvd_usr_id', (int) $filtros['vendedor_id']);
            })
            ->when(!empty($filtros['linea_id']), function ($query) use ($filtros): void {
                $query->where('prd.prd_lna_id', (int) $filtros['linea_id']);
            })
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('usr.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('alm.alm_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('lna.lna_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psv.psv_folio', 'like', "%{$buscar}%")
                        ->orWhere('psv_origen.psv_folio', 'like', "%{$buscar}%");
                });
            })
            ->leftJoin('tbl_pos_ventas_psv as psv_origen', 'psv_origen.psv_id', '=', 'pcd.pcd_psv_origen_id')
            ->groupBy(
                'psv.psv_id',
                'psv.psv_fecha_cobro',
                'psv.psv_folio',
                'psv_origen.psv_folio',
                'usr.usr_nombre',
                'alm.alm_nombre',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_codigo',
                'prd.prd_nombre',
                'lna.lna_nombre',
                'pcd.pcd_precio_unitario',
                'pvd.pvd_descuento_porcentaje'
            )
            ->selectRaw('
                "Cambio devolución" as movimiento_tipo,
                psv.psv_fecha_cobro as movimiento_fecha,
                psv.psv_folio as folio,
                psv_origen.psv_folio as referencia_folio,
                COALESCE(NULLIF(usr.usr_nombre, ""), alm.alm_nombre, "Sin vendedor") as vendedor_nombre,
                psk.psk_codigo as sku_codigo,
                psk.psk_nombre as sku_nombre,
                prd.prd_codigo as producto_codigo,
                prd.prd_nombre as producto_nombre,
                COALESCE(lna.lna_nombre, "Sin línea") as linea_nombre,
                ROUND(SUM(pcd.pcd_cantidad) * -1, 2) as piezas_vendidas,
                COUNT(DISTINCT psv.psv_id) as tickets,
                ROUND(SUM(pcd.pcd_importe_credito) * -1, 2) as importe_total,
                ROUND(SUM(((pcd.pcd_cantidad * pcd.pcd_precio_unitario) - pcd.pcd_importe_credito)) * -1, 2) as descuento_total,
                MAX(COALESCE(pvd.pvd_descuento_porcentaje, 0)) as descuento_porcentaje_max,
                ROUND(MAX(pcd.pcd_precio_unitario), 2) as precio_unitario
            ')
            ->get()
            ->map(fn ($row) => $this->mapReporteVentaVendedorRow($row))
            ->values();
    }

    private function reporteVentasVendedoresCambioNuevos(array $filtros, Carbon $desde, Carbon $hasta): Collection
    {
        $buscar = trim((string) ($filtros['q'] ?? ''));

        return DB::table('tbl_pos_venta_detalle_pvd as pvd')
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'pvd.pvd_psv_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pvd.pvd_usr_id')
            ->join('tbl_producto_skus_psk as psk', 'psk.psk_id', '=', 'pvd.pvd_psk_id')
            ->join('tbl_productos_prd as prd', 'prd.prd_id', '=', 'psk.psk_prd_id')
            ->leftJoin('tbl_lineas_lna as lna', 'lna.lna_id', '=', 'prd.prd_lna_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->where('pvd.pvd_deleted', false)
            ->whereNull('pvd.pvd_deleted_at')
            ->where('psv.psv_deleted', false)
            ->whereNull('psv.psv_deleted_at')
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->where('psv.psv_tipo_operacion', 'cambio')
            ->where('psk.psk_deleted', false)
            ->whereNull('psk.psk_deleted_at')
            ->where('prd.prd_deleted', false)
            ->whereNull('prd.prd_deleted_at')
            ->whereBetween('psv.psv_fecha_cobro', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when(!empty($filtros['vendedor_id']), function ($query) use ($filtros): void {
                $query->where('pvd.pvd_usr_id', (int) $filtros['vendedor_id']);
            })
            ->when(!empty($filtros['linea_id']), function ($query) use ($filtros): void {
                $query->where('prd.prd_lna_id', (int) $filtros['linea_id']);
            })
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($sub) use ($buscar): void {
                    $sub->where('usr.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('alm.alm_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_codigo', 'like', "%{$buscar}%")
                        ->orWhere('psk.psk_nombre', 'like', "%{$buscar}%")
                        ->orWhere('prd.prd_nombre', 'like', "%{$buscar}%")
                        ->orWhere('lna.lna_nombre', 'like', "%{$buscar}%")
                        ->orWhere('psv.psv_folio', 'like', "%{$buscar}%")
                        ->orWhere('psv_origen.psv_folio', 'like', "%{$buscar}%");
                });
            })
            ->leftJoin('tbl_pos_ventas_psv as psv_origen', 'psv_origen.psv_id', '=', 'psv.psv_venta_origen_id')
            ->groupBy(
                'psv.psv_id',
                'psv.psv_fecha_cobro',
                'psv.psv_folio',
                'psv_origen.psv_folio',
                'usr.usr_nombre',
                'alm.alm_nombre',
                'psk.psk_codigo',
                'psk.psk_nombre',
                'prd.prd_codigo',
                'prd.prd_nombre',
                'lna.lna_nombre',
                'pvd.pvd_precio_unitario',
                'pvd.pvd_descuento_porcentaje'
            )
            ->selectRaw('
                "Cambio nuevo" as movimiento_tipo,
                psv.psv_fecha_cobro as movimiento_fecha,
                psv.psv_folio as folio,
                psv_origen.psv_folio as referencia_folio,
                COALESCE(NULLIF(usr.usr_nombre, ""), alm.alm_nombre, "Sin vendedor") as vendedor_nombre,
                psk.psk_codigo as sku_codigo,
                psk.psk_nombre as sku_nombre,
                prd.prd_codigo as producto_codigo,
                prd.prd_nombre as producto_nombre,
                COALESCE(lna.lna_nombre, "Sin línea") as linea_nombre,
                ROUND(SUM(pvd.pvd_cantidad), 2) as piezas_vendidas,
                COUNT(DISTINCT psv.psv_id) as tickets,
                ROUND(SUM(pvd.pvd_importe), 2) as importe_total,
                ROUND(SUM(COALESCE(pvd.pvd_descuento_importe, 0)), 2) as descuento_total,
                MAX(COALESCE(pvd.pvd_descuento_porcentaje, 0)) as descuento_porcentaje_max,
                ROUND(MAX(pvd.pvd_precio_unitario), 2) as precio_unitario
            ')
            ->get()
            ->map(fn ($row) => $this->mapReporteVentaVendedorRow($row))
            ->values();
    }

    private function mapReporteVentaVendedorRow(object $row): array
    {
        $fecha = $row->movimiento_fecha ? Carbon::parse($row->movimiento_fecha) : null;

        return [
            'tipo' => (string) ($row->movimiento_tipo ?? 'Venta'),
            'folio' => (string) ($row->folio ?? ''),
            'referencia' => $row->referencia_folio ? (string) $row->referencia_folio : null,
            'vendedor' => (string) $row->vendedor_nombre,
            'linea' => (string) ($row->linea_nombre ?? 'Sin línea'),
            'producto' => (string) ($row->sku_nombre ?: $row->producto_nombre),
            'codigo' => (string) ($row->sku_codigo ?: $row->producto_codigo ?: ''),
            'piezas_vendidas' => (float) $row->piezas_vendidas,
            'tickets' => (int) $row->tickets,
            'importe_total' => (float) $row->importe_total,
            'descuento_total' => (float) $row->descuento_total,
            'descuento_porcentaje_max' => (float) $row->descuento_porcentaje_max,
            'tiene_descuento' => abs((float) $row->descuento_total) > 0 || (float) $row->descuento_porcentaje_max > 0,
            'precio_unitario' => (float) $row->precio_unitario,
            'ultima_venta' => $fecha ? $fecha->format('Y-m-d H:i') : null,
            'movimiento_fecha_sort' => $fecha ? $fecha->timestamp : 0,
        ];
    }

    private function resolverRangoReporte(array $filtros): array
    {
        $modo = (string) ($filtros['tiempo'] ?? 'semana');
        $hoy = now();

        return match ($modo) {
            'hoy' => [$hoy->copy(), $hoy->copy()],
            'ayer' => [$hoy->copy()->subDay(), $hoy->copy()->subDay()],
            'antier' => [$hoy->copy()->subDays(2), $hoy->copy()->subDays(2)],
            'mes' => [$hoy->copy()->startOfMonth(), $hoy->copy()],
            'rango' => [
                !empty($filtros['desde']) ? Carbon::parse((string) $filtros['desde']) : $hoy->copy()->startOfWeek(Carbon::MONDAY),
                !empty($filtros['hasta']) ? Carbon::parse((string) $filtros['hasta']) : $hoy->copy(),
            ],
            default => [$hoy->copy()->startOfWeek(Carbon::MONDAY), $hoy->copy()],
        };
    }
}
