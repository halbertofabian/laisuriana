<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StorePedidoPisoRequest;
use App\Models\PedidoPiso;
use App\Services\Operacion\EscaneoProductoService;
use App\Services\Operacion\PedidoPisoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoPisoController extends Controller
{
    public function __construct(
        private readonly PedidoPisoService $pedidoPisoService,
        private readonly EscaneoProductoService $escaneoProductoService,
    ) {
    }

    public function index()
    {
        $opciones = $this->pedidoPisoService->opcionesBase();
        $usuario = auth()->user();
        $defaultSucursalId = $usuario?->sucursales()
            ->orderBy('tbl_sucursales_scl.scl_nombre')
            ->value('tbl_sucursales_scl.scl_id');

        if (!$defaultSucursalId && $opciones['sucursales']->count() === 1) {
            $defaultSucursalId = (int) $opciones['sucursales']->first()->scl_id;
        }

        return view('operacion.pedidos_piso.index', [
            'opciones' => $opciones,
            'defaultSucursalId' => $defaultSucursalId ? (int) $defaultSucursalId : null,
            'usuarioActual' => [
                'usr_id' => (int) (auth()->user()?->usr_id ?? 0),
                'usr_nombre' => (string) (auth()->user()?->usr_nombre ?? 'Sin usuario'),
            ],
            'permisosUI' => [
                'crear' => auth()->user()?->tienePermiso('pedido_piso.crear') ?? false,
                'ver' => auth()->user()?->tienePermiso('pedido_piso.ver') ?? false,
                'eliminar' => (auth()->user()?->tienePermiso('pedido_piso.eliminar') ?? false)
                    || (auth()->user()?->tienePermiso('pedido_piso.crear') ?? false),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filtros = $request->only(['buscar', 'pdp_scl_id']);
        $filtros['pdp_estatus'] = 'pendiente_cobro';

        $rows = $this->pedidoPisoService->listar($filtros);

        $data = $rows->map(fn ($r) => [
            'pdp_id' => $r->pdp_id,
            'pdp_folio' => $r->pdp_folio,
            'pdp_estatus' => $r->pdp_estatus,
            'pdp_total' => (float) $r->pdp_total,
            'pdp_fecha' => optional($r->pdp_fecha)->format('Y-m-d H:i:s'),
            'sucursal' => $r->sucursal?->scl_nombre,
            'almacen' => $r->almacen?->alm_nombre,
            'vendedor' => $r->usuario?->usr_nombre,
            'cliente' => $this->mapCliente($r->cliente),
        ])->values();

        return response()->json(['data' => $data]);
    }

    public function show(int $pedido): JsonResponse
    {
        $r = $this->pedidoPisoService->obtenerPorId($pedido);

        return response()->json([
            'data' => [
                'pdp_id' => $r->pdp_id,
                'pdp_folio' => $r->pdp_folio,
                'pdp_scl_id' => (int) $r->pdp_scl_id,
                'pdp_alm_id' => (int) $r->pdp_alm_id,
                'pdp_cli_id' => (int) ($r->pdp_cli_id ?? 0),
                'pdp_estatus' => $r->pdp_estatus,
                'pdp_subtotal' => (float) $r->pdp_subtotal,
                'pdp_total' => (float) $r->pdp_total,
                'pdp_observaciones' => $r->pdp_observaciones,
                'sucursal' => $r->sucursal?->scl_nombre,
                'almacen' => $r->almacen?->alm_nombre,
                'vendedor' => $r->usuario?->usr_nombre,
                'cliente' => $this->mapCliente($r->cliente),
                'detalle' => $r->detalle->map(fn ($d) => [
                    'ppd_id' => (int) $d->ppd_id,
                    'ppd_psk_id' => $d->ppd_psk_id,
                    'sku' => $d->sku?->psk_codigo,
                    'nombre' => $d->sku?->psk_nombre,
                    'cantidad' => (float) $d->ppd_cantidad,
                    'precio' => (float) $d->ppd_precio_unitario,
                    'importe' => (float) $d->ppd_importe,
                    'descuento_tipo' => (string) ($d->ppd_descuento_tipo ?? 'ninguno'),
                    'descuento_valor' => (float) ($d->ppd_descuento_valor ?? 0),
                    'descuento_importe' => (float) ($d->ppd_descuento_importe ?? 0),
                    'descuento_cantidad' => (float) ($d->ppd_descuento_cantidad ?? 0),
                    'total_linea' => (float) ($d->ppd_total_linea ?? $d->ppd_importe ?? 0),
                    'ppd_usr_id' => (int) ($d->ppd_usr_id ?? 0),
                    'capturista' => $d->capturista?->usr_nombre,
                    'permite_decimal' => (bool) ($d->sku?->producto && strtoupper(trim((string) ($d->sku->producto->unidad?->umd_codigo ?? ''))) === 'M'),
                ])->values(),
            ],
        ]);
    }

    public function store(StorePedidoPisoRequest $request): JsonResponse
    {
        $pedido = $this->pedidoPisoService->crear($request, $request->validated());

        return response()->json([
            'message' => 'Pedido creado correctamente.',
            'data' => [
                'pdp_id' => $pedido->pdp_id,
                'pdp_folio' => $pedido->pdp_folio,
            ],
        ]);
    }

    public function update(StorePedidoPisoRequest $request, int $pedido): JsonResponse
    {
        $pedidoActualizado = $this->pedidoPisoService->actualizar($request, $pedido, $request->validated());

        return response()->json([
            'message' => 'Pedido actualizado correctamente.',
            'data' => [
                'pdp_id' => $pedidoActualizado->pdp_id,
                'pdp_folio' => $pedidoActualizado->pdp_folio,
            ],
        ]);
    }

    public function eliminar(Request $request, int $pedido): JsonResponse
    {
        $this->pedidoPisoService->eliminar($request, $pedido);

        return response()->json([
            'message' => 'Pedido eliminado correctamente.',
        ]);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        return response()->json([
            'data' => $this->escaneoProductoService->sugerencias((string) $datos['q'], 15),
        ]);
    }

    public function resolverProductoAlmacen(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'pdp_scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
        ]);

        $resultado = $this->pedidoPisoService->resolverSkuAlmacen(
            (int) $datos['psk_id'],
            (int) $datos['pdp_scl_id']
        );

        if (!$resultado['valido']) {
            return response()->json([
                'message' => $resultado['message'],
                'data' => $resultado,
            ], 422);
        }

        return response()->json([
            'message' => $resultado['message'],
            'data' => $resultado,
        ]);
    }

    public function validarProductoAlmacen(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'pdp_scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
            'pdp_alm_id' => ['required', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
        ]);

        $resultado = $this->pedidoPisoService->validarSkuParaAlmacen(
            (int) $datos['psk_id'],
            (int) $datos['pdp_scl_id'],
            (int) $datos['pdp_alm_id']
        );

        if (!$resultado['valido']) {
            return response()->json([
                'message' => $resultado['message'],
                'data' => $resultado,
            ], 422);
        }

        return response()->json([
            'message' => $resultado['message'],
            'data' => $resultado,
        ]);
    }

    public function buscarPorFolio(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
        ], [
            'folio.required' => 'Captura el folio del pedido.',
        ]);

        $pedido = $this->pedidoPisoService->obtenerPorFolio((string) $datos['folio']);
        if (!$pedido) {
            return response()->json([
                'message' => 'No encontramos el pedido solicitado.',
            ], 404);
        }

        if ($pedido->pdp_estatus !== 'pendiente_cobro') {
            return response()->json([
                'message' => 'El pedido no está disponible para carga en caja.',
                'data' => ['pdp_estatus' => $pedido->pdp_estatus],
            ], 422);
        }

        return response()->json([
            'message' => 'Pedido localizado correctamente.',
            'data' => [
                'pdp_id' => $pedido->pdp_id,
                'pdp_folio' => $pedido->pdp_folio,
                'pdp_estatus' => $pedido->pdp_estatus,
                'pdp_subtotal' => (float) $pedido->pdp_subtotal,
                'pdp_total' => (float) $pedido->pdp_total,
                'pdp_observaciones' => $pedido->pdp_observaciones,
                'pdp_alm_id' => $pedido->pdp_alm_id,
                'pdp_cli_id' => (int) ($pedido->pdp_cli_id ?? 0),
                'almacen' => $pedido->almacen?->alm_nombre,
                'sucursal' => $pedido->sucursal?->scl_nombre,
                'vendedor' => $pedido->usuario?->usr_nombre,
                'cliente' => $this->mapCliente($pedido->cliente),
                'detalle' => $pedido->detalle->map(fn ($d) => [
                    'ppd_id' => (int) $d->ppd_id,
                    'ppd_psk_id' => $d->ppd_psk_id,
                    'sku' => $d->sku?->psk_codigo,
                    'nombre' => $d->sku?->psk_nombre,
                    'cantidad' => (float) $d->ppd_cantidad,
                    'precio' => (float) $d->ppd_precio_unitario,
                    'importe' => (float) $d->ppd_importe,
                    'descuento_tipo' => (string) ($d->ppd_descuento_tipo ?? 'ninguno'),
                    'descuento_valor' => (float) ($d->ppd_descuento_valor ?? 0),
                    'descuento_importe' => (float) ($d->ppd_descuento_importe ?? 0),
                    'descuento_cantidad' => (float) ($d->ppd_descuento_cantidad ?? 0),
                    'total_linea' => (float) ($d->ppd_total_linea ?? $d->ppd_importe ?? 0),
                    'ppd_usr_id' => (int) ($d->ppd_usr_id ?? 0),
                    'capturista' => $d->capturista?->usr_nombre,
                ])->values(),
            ],
        ]);
    }

    private function mapCliente($cliente): ?array
    {
        if (!$cliente) {
            return null;
        }

        $nombre = trim((string) ($cliente->cli_razon_social
            ?: implode(' ', array_filter([
                $cliente->cli_nombre,
                $cliente->cli_apellido_paterno,
                $cliente->cli_apellido_materno,
            ]))));

        return [
            'cli_id' => (int) $cliente->cli_id,
            'nombre' => $nombre,
            'telefono' => (string) ($cliente->cli_telefono ?? ''),
            'email' => (string) ($cliente->cli_email ?? ''),
            'rfc' => (string) ($cliente->cli_rfc ?? ''),
            'descuento_default' => $cliente->cli_descuento_default !== null ? (int) $cliente->cli_descuento_default : null,
        ];
    }

    public function ticket(PedidoPiso $pedido)
    {
        $pedido->load([
            'sucursal:scl_id,scl_nombre',
            'almacen:alm_id,alm_nombre',
            'usuario:usr_id,usr_nombre',
            'detalle.capturista:usr_id,usr_nombre',
        ]);

        $vendedores = $pedido->detalle
            ->pluck('capturista.usr_nombre')
            ->filter(fn ($nombre) => filled($nombre))
            ->unique()
            ->values();

        if ($vendedores->isEmpty() && filled($pedido->usuario?->usr_nombre)) {
            $vendedores = collect([(string) $pedido->usuario->usr_nombre]);
        }

        $pdf = new \TCPDF('P', 'mm', [80, 95], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana'));
        $pdf->SetAuthor((string) ($pedido->usuario?->usr_nombre ?? 'Pedido piso'));
        $pdf->SetTitle('Pedido ' . $pedido->pdp_folio);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(3, 3, 3);
        $pdf->SetAutoPageBreak(true, 2);
        $pdf->AddPage();

        $barcodeStyle = [
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'padding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 7,
        ];

        $html = '<div style="text-align:center;font-size:12px;font-weight:bold;line-height:1.05;">' . e((string) ($pedido->sucursal?->scl_nombre ?? 'Sucursal')) . '</div>';
        $html .= '<div style="text-align:center;font-size:6.6px;color:#666;line-height:1.05;margin-top:1px;">Pedido de piso</div>';
        $html .= '<div style="text-align:center;font-size:8.6px;font-weight:bold;line-height:1.08;margin-top:3px;">' . e((string) ($pedido->almacen?->alm_nombre ?? 'Sin almacÃ©n')) . '</div>';
        if ($vendedores->isNotEmpty()) {
            $html .= '<div style="text-align:center;font-size:6.5px;color:#444;line-height:1.12;margin-top:1px;">' . e($vendedores->join(', ')) . '</div>';
        }
        $html .= '<div style="border-top:1px solid #666;margin-top:4px;padding-top:3px;">';
        $html .= '<table cellspacing="0" cellpadding="0.8" style="font-size:8px;width:100%;">';
        $html .= '<tr><td width="28%"><b>Almacén</b></td><td width="72%" align="right">' . e((string) ($pedido->almacen?->alm_nombre ?? 'Sin almacén')) . '</td></tr>';
        $html .= '<tr><td width="26%"><b>Folio</b></td><td width="74%" align="right" style="font-weight:bold;">' . e((string) $pedido->pdp_folio) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '<div style="text-align:center;font-size:6.4px;color:#666;line-height:1.05;margin-top:2px;">Presenta este ticket en caja</div>';

        $html = '<div style="text-align:center;font-size:12px;font-weight:bold;line-height:1.05;">' . e((string) ($pedido->sucursal?->scl_nombre ?? 'Sucursal')) . '</div>';
        $html .= '<div style="text-align:center;font-size:6.6px;color:#666;line-height:1.05;margin-top:1px;">Pedido de piso</div>';
        $html .= '<div style="text-align:center;font-size:8.6px;font-weight:bold;line-height:1.08;margin-top:3px;">' . e((string) ($pedido->almacen?->alm_nombre ?? 'Sin almacen')) . '</div>';
        if ($vendedores->isNotEmpty()) {
            $html .= '<div style="text-align:center;font-size:6.5px;color:#444;line-height:1.12;margin-top:1px;">' . e($vendedores->join(', ')) . '</div>';
        }
        $html .= '<div style="border-top:1px solid #666;margin-top:4px;padding-top:3px;">';
        $html .= '<table cellspacing="0" cellpadding="0.8" style="font-size:8px;width:100%;">';
        $html .= '<tr><td width="26%"><b>Folio</b></td><td width="74%" align="right" style="font-weight:bold;">' . e((string) $pedido->pdp_folio) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '<div style="text-align:center;font-size:6.4px;color:#666;line-height:1.05;margin-top:2px;">Presenta este ticket en caja</div>';

        $html = '<div style="text-align:center;font-size:11px;font-weight:bold;line-height:1.0;">' . e((string) ($pedido->sucursal?->scl_nombre ?? 'Sucursal')) . '</div>';
        $html .= '<div style="text-align:center;font-size:6px;color:#666;line-height:1.0;margin-top:0.5px;">Pedido de piso</div>';
        $html .= '<div style="text-align:center;font-size:8.2px;font-weight:bold;line-height:1.02;margin-top:1.5px;">' . e((string) ($pedido->almacen?->alm_nombre ?? 'Sin almacen')) . '</div>';
        if ($vendedores->isNotEmpty()) {
            $html .= '<div style="text-align:center;font-size:6px;color:#444;line-height:1.0;margin-top:0.5px;">' . e($vendedores->join(', ')) . '</div>';
        }
        $html .= '<table cellspacing="0" cellpadding="0.35" style="font-size:7.8px;width:100%;margin-top:1.5px;border-top:1px solid #666;">';
        $html .= '<tr><td width="24%"><b>Folio</b></td><td width="76%" align="right" style="font-weight:bold;">' . e((string) $pedido->pdp_folio) . '</td></tr>';
        $html .= '</table>';
        $html .= '<div style="text-align:center;font-size:5.8px;color:#666;line-height:1.0;margin-top:0.5px;">Presenta este ticket en caja</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $barcodeY = max(22, $pdf->GetY() + 0.5);
        $pdf->write1DBarcode(
            (string) $pedido->pdp_folio,
            'C128',
            8,
            $barcodeY,
            64,
            12,
            0.33,
            $barcodeStyle,
            'N'
        );
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetXY(5, $barcodeY + 12.5);
        $pdf->Cell(70, 3.5, (string) $pedido->pdp_folio, 0, 1, 'C');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pedido-' . $pedido->pdp_folio . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
