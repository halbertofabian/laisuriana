<?php

namespace App\Http\Controllers\Desktop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\Inventario\ListExistenciaMatrizRequest;
use App\Http\Requests\Operacion\Inventario\ShowKardexDetalleRequest;
use App\Services\Operacion\ExistenciaMatrizService;
use App\Services\Operacion\InventarioBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperacionInventarioController extends Controller
{
    public function __construct(
        private readonly InventarioBaseService $inventarioService,
        private readonly ExistenciaMatrizService $existenciaMatrizService,
    ) {
    }

    public function index()
    {
        return redirect()->route('desktop.operacion.inventario.existencias.index');
    }

    public function existencias()
    {
        $opciones = $this->inventarioService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('desktop.operacion.inventario.existencias', [
            'submenus' => $this->submenus(),
            'opciones' => $opciones,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
        ]);
    }

    public function kardexDetalle(ShowKardexDetalleRequest $request, int $sku)
    {
        return view('desktop.operacion.inventario.kardex_detalle', [
            'submenus' => $this->submenus(),
            'detalle' => $this->inventarioService->obtenerKardexDetalleSku($sku, $request->validated()),
        ]);
    }

    public function existenciasNegativas()
    {
        return $this->renderPlaceholder('existencias_negativas', 'Existencias negativas');
    }

    public function negativosSesion()
    {
        return $this->renderPlaceholder('negativos_sesion', 'Negativos por sesión');
    }

    public function recibir()
    {
        return $this->renderPlaceholder('recibir', 'Recibir mercancía');
    }

    public function recepciones()
    {
        return $this->renderPlaceholder('recepciones', 'Recepciones capturadas');
    }

    public function salidas()
    {
        return $this->renderPlaceholder('salidas', 'Salidas');
    }

    public function kardex()
    {
        return $this->renderPlaceholder('kardex', 'Kardex');
    }

    public function minimos()
    {
        return $this->renderPlaceholder('minimos', 'Bajo mínimo');
    }

    public function reportes()
    {
        return $this->renderPlaceholder('reportes', 'Reportes PDF');
    }

    public function dataExistenciasMatriz(ListExistenciaMatrizRequest $request): JsonResponse
    {
        $filtros = $request->only([
            'prd_id',
            'prd_mrc_id',
            'prd_mdl_id',
            'prd_lna_id',
            'prd_ctg_id',
            'buscar',
            'min_scl_id',
            'min_alm_id',
        ]);

        $buscarDatatable = trim((string) $request->input('search.value', ''));
        if ($buscarDatatable !== '') {
            $filtros['buscar'] = $buscarDatatable;
        }

        $resultado = $this->existenciaMatrizService->paginarDataTable(
            filtros: $filtros,
            start: (int) $request->integer('start', 0),
            length: (int) $request->integer('length', 10),
            orderColumn: (int) $request->input('order.0.column', 0),
            orderDir: (string) $request->input('order.0.dir', 'asc'),
        );

        return response()->json([
            'draw' => (int) $request->integer('draw', 1),
            'recordsTotal' => (int) $resultado['recordsTotal'],
            'recordsFiltered' => (int) $resultado['recordsFiltered'],
            'data' => $resultado['data'],
        ]);
    }

    public function buscarProductosBase(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $resultado = $this->inventarioService->buscarProductosBase(
            (string) ($datos['q'] ?? ''),
            (int) ($datos['page'] ?? 1),
            20,
            $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id'])
        );

        return response()->json($resultado);
    }

    public function exportarExcelExistenciasMatriz(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id']);
        $filas = $this->existenciaMatrizService->exportarTodos($filtros);
        $fileName = 'existencias-matriz-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($filas): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Marca', 'Modelo', 'Línea', 'Concepto', 'Producto', 'Color', 'Tallas', 'Total Art.', 'Precio Unit.', 'Costo Unit.', 'Total Importe']);

            foreach ($filas as $fila) {
                $tallasStr = collect($fila['tallas'] ?? [])
                    ->map(fn ($t) => ($t['talla'] ?? 'Base') . ':' . ($t['existencia'] === null ? 'N/D' : number_format((float) $t['existencia'], 0, '.', '')))
                    ->implode(' / ');

                fputcsv($handle, [
                    $fila['marca_nombre'] ?? '',
                    $fila['modelo_nombre'] ?? '',
                    $fila['linea_nombre'] ?? '',
                    $fila['concepto_nombre'] ?? '',
                    ($fila['producto_codigo'] ?? '') . ' - ' . ($fila['producto_nombre'] ?? ''),
                    $fila['color_nombre'] ?? '',
                    $tallasStr,
                    $fila['total_articulos'] ?? 0,
                    $fila['precio_unitario'] ?? 0,
                    $fila['costo_unitario'] ?? 0,
                    $fila['total_importe_precio'] ?? 0,
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    public function exportarPdfExistenciasMatriz(Request $request): \Illuminate\Http\Response
    {
        $filtros = $request->only(['prd_mrc_id', 'prd_mdl_id', 'prd_lna_id', 'prd_ctg_id', 'prd_id', 'buscar', 'min_scl_id', 'min_alm_id']);
        $filas = $this->existenciaMatrizService->exportarTodos($filtros);

        $filas = collect($filas);
        $totalArt = $filas->sum(fn ($f) => (float) ($f['total_articulos'] ?? 0));
        $totalImp = $filas->sum(fn ($f) => (float) ($f['total_importe_precio'] ?? 0));
        $filas = $filas->all();

        $rowsHtml = '';
        $rowIdx = 0;
        foreach ($filas as $fila) {
            $tallasStr = collect($fila['tallas'] ?? [])
                ->map(fn ($t) => '<b>' . htmlspecialchars($t['talla'] ?? 'Base') . '</b>:' . ($t['existencia'] === null ? '<i>N/D</i>' : number_format((float) $t['existencia'], 0, '.', '')))
                ->implode(' &nbsp;');

            $bg = ($rowIdx % 2 === 0) ? '#f8fafc' : '#ffffff';
            $rowIdx++;

            $rowsHtml .= '<tr style="background:' . $bg . '">'
                . '<td>' . htmlspecialchars($fila['marca_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['modelo_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['linea_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($fila['concepto_nombre'] ?? '-') . '</td>'
                . '<td>' . htmlspecialchars(($fila['producto_codigo'] ?? '') . ' ' . ($fila['producto_nombre'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars($fila['color_nombre'] ?? '-') . '</td>'
                . '<td style="font-size:7px">' . $tallasStr . '</td>'
                . '<td style="text-align:right">' . number_format((float) ($fila['total_articulos'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['precio_unitario'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['costo_unitario'] ?? 0), 2) . '</td>'
                . '<td style="text-align:right">$ ' . number_format((float) ($fila['total_importe_precio'] ?? 0), 2) . '</td>'
                . '</tr>';
        }

        $fechaGen = now()->format('d/m/Y H:i:s');
        $totalRows = count($filas);

        $html = '
        <style>
            body { font-family: helvetica; font-size: 8px; color: #1e293b; }
            h2 { font-size: 12px; color: #1e3a5f; margin: 0 0 2px 0; }
            .meta { font-size: 7px; color: #64748b; margin-bottom: 6px; }
            table { border-collapse: collapse; width: 100%; }
            th { background: #1e3a5f; color: #fff; font-size: 7.5px; padding: 4px 3px; text-align: left; }
            td { font-size: 7.5px; padding: 3px; border-bottom: 1px solid #e2e8f0; }
            .footer { font-size: 6.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 3px; margin-top: 6px; }
        </style>
        <h2>Existencias Matriz</h2>
        <div class="meta">' . $totalRows . ' registros &bull; Generado: ' . $fechaGen . '</div>
        <table>
            <thead>
                <tr>
                    <th>Marca</th><th>Modelo</th><th>Línea</th><th>Concepto</th>
                    <th>Producto</th><th>Color</th><th>Tallas</th>
                    <th style="text-align:right">Total Art.</th>
                    <th style="text-align:right">Precio</th>
                    <th style="text-align:right">Costo</th>
                    <th style="text-align:right">Total $</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
            <tfoot>
                <tr style="background:#f1f5f9">
                    <td colspan="7"><b>TOTAL</b></td>
                    <td style="text-align:right"><b>' . number_format($totalArt, 2) . '</b></td>
                    <td colspan="2"></td>
                    <td style="text-align:right"><b>$ ' . number_format($totalImp, 2) . '</b></td>
                </tr>
            </tfoot>
        </table>
        <div class="footer">La I. Suriana &bull; Documento de uso interno &bull; ' . $fechaGen . '</div>';

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana Retail'));
        $pdf->SetAuthor((string) (request()->user()?->name ?? config('app.name')));
        $pdf->SetTitle('Existencias Matriz');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'existencias-matriz-' . now()->format('Ymd-His') . '.pdf';
        $content = $pdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length' => strlen($content),
        ]);
    }

    private function submenus(): array
    {
        return [
            ['key' => 'existencias', 'label' => 'Existencias', 'route' => route('desktop.operacion.inventario.existencias.index')],
            ['key' => 'existencias_negativas', 'label' => 'Existencias negativas', 'route' => route('desktop.operacion.inventario.existencias_negativas.index')],
            ['key' => 'negativos_sesion', 'label' => 'Negativos por sesión', 'route' => route('desktop.operacion.inventario.negativos_sesion.index')],
            ['key' => 'recibir', 'label' => 'Recibir mercancía', 'route' => route('desktop.operacion.inventario.recibir.index')],
            ['key' => 'recepciones', 'label' => 'Recepciones capturadas', 'route' => route('desktop.operacion.inventario.recepciones.index')],
            ['key' => 'salidas', 'label' => 'Salidas', 'route' => route('desktop.operacion.inventario.salidas.index')],
            ['key' => 'kardex', 'label' => 'Kardex', 'route' => route('desktop.operacion.inventario.kardex.index')],
            ['key' => 'minimos', 'label' => 'Bajo mínimo', 'route' => route('desktop.operacion.inventario.minimos.index')],
            ['key' => 'reportes', 'label' => 'Reportes PDF', 'route' => route('desktop.operacion.inventario.reportes.index')],
        ];
    }

    private function renderPlaceholder(string $activeSubmenu, string $pageTitle)
    {
        return view('desktop.operacion.inventario.placeholder', [
            'activeSubmenu' => $activeSubmenu,
            'pageTitle' => $pageTitle,
            'submenus' => $this->submenus(),
        ]);
    }
}
