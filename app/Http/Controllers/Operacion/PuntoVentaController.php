<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\CancelPosVentaRequest;
use App\Http\Requests\Operacion\StorePosVentaRequest;
use App\Http\Requests\Operacion\StorePosCambioRequest;
use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\PosVenta;
use App\Models\Caja;
use App\Models\PosTicketConfiguracion;
use App\Models\ProductoSku;
use App\Models\Usuario;
use App\Services\Operacion\PosCambioVentaService;
use App\Services\Operacion\PosCajaSesionService;
use App\Services\Operacion\PosVentaCancelacionService;
use App\Services\Operacion\PosVentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuntoVentaController extends Controller
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly PosVentaService $posVentaService,
        private readonly PosVentaCancelacionService $posVentaCancelacionService,
        private readonly PosCambioVentaService $posCambioVentaService,
    ) {
    }

    public function index(Request $request)
    {
        $usuario = $request->user();
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sucursal = $estado['sesion_activa']['sucursal']
            ?? $usuario->sucursales()->orderByDesc('tbl_usuario_sucursales_usc.usc_es_predeterminada')->value('scl_nombre')
            ?? 'Sin sucursal';
        $caja = $estado['sesion_activa']['caja_nombre'] ?? 'Sin caja activa';
        $sucursalIds = $usuario->sucursales()->pluck('tbl_sucursales_scl.scl_id');

        $almacenesVenta = Almacen::query()
            ->whereIn('alm_scl_id', $sucursalIds)
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_nombre')
            ->get(['alm_id', 'alm_scl_id', 'alm_nombre'])
            ->map(fn (Almacen $a) => [
                'alm_id' => (int) $a->alm_id,
                'alm_scl_id' => (int) $a->alm_scl_id,
                'alm_nombre' => (string) $a->alm_nombre,
            ])
            ->values();
        $puedeCrearCliente = $usuario?->tienePermiso('cliente.crear') ?? false;
        $puedeCancelarVenta = $usuario?->tienePermiso('pos.cancelar_venta') ?? false;
        $puedeRegistrarCambio = $usuario?->tienePermiso('pos.cambio_devolucion') ?? false;
        $vendedores = Usuario::query()
            ->where('usr_estatus', 'activo')
            ->where('usr_deleted', false)
            ->whereNull('usr_deleted_at')
            ->orderBy('usr_nombre')
            ->get(['usr_id', 'usr_nombre', 'usr_usuario'])
            ->map(fn (Usuario $u) => [
                'usr_id' => (int) $u->usr_id,
                'usr_nombre' => (string) $u->usr_nombre,
                'usr_usuario' => (string) $u->usr_usuario,
            ])
            ->values();

        return view('operacion.punto_venta.index', compact(
            'sucursal',
            'caja',
            'estado',
            'almacenesVenta',
            'puedeCrearCliente',
            'puedeCancelarVenta',
            'puedeRegistrarCambio',
            'vendedores'
        ));
    }

    public function estadoCaja(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->posCajaSesionService->estadoUsuario($request->user()),
        ]);
    }

    public function abrirCaja(Request $request): JsonResponse
    {
        $data = $request->validate([
            'caja_id' => ['required', 'integer'],
            'monto_apertura' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sesion = $this->posCajaSesionService->abrirCaja(
            $request->user(),
            (int) $data['caja_id'],
            (float) ($data['monto_apertura'] ?? 0)
        );

        return response()->json([
            'message' => 'Caja abierta correctamente.',
            'data' => ['caja_nombre' => $sesion->caja?->caj_nombre],
        ]);
    }

    public function tomarCaja(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sesion_id' => ['required', 'integer'],
        ]);

        $sesion = $this->posCajaSesionService->tomarCajaAbierta($request->user(), (int) $data['sesion_id']);

        return response()->json([
            'message' => 'Sesión de caja vinculada correctamente.',
            'data' => ['caja_nombre' => $sesion->caja?->caj_nombre],
        ]);
    }

    public function abandonarCaja(Request $request): JsonResponse
    {
        $this->posCajaSesionService->abandonarSesion($request->user());

        return response()->json([
            'message' => 'Se cerró tu sesión de trabajo en caja.',
        ]);
    }

    public function buscarClientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $clientes = Cliente::query()
            ->where('cli_estatus', 'activo')
            ->where('cli_deleted', false)
            ->whereNull('cli_deleted_at')
            ->where(function ($sub) use ($q): void {
                $sub->where('cli_nombre', 'like', "%{$q}%")
                    ->orWhere('cli_apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('cli_apellido_materno', 'like', "%{$q}%")
                    ->orWhere('cli_rfc', 'like', "%{$q}%")
                    ->orWhere('cli_curp', 'like', "%{$q}%")
                    ->orWhere('cli_email', 'like', "%{$q}%")
                    ->orWhere('cli_telefono', 'like', "%{$q}%")
                    ->orWhere('cli_whatsapp', 'like', "%{$q}%");
            })
            ->orderBy('cli_nombre')
            ->limit(20)
            ->get()
            ->map(function (Cliente $c): array {
                $nombre = trim(implode(' ', array_filter([$c->cli_nombre, $c->cli_apellido_paterno, $c->cli_apellido_materno])));
                return [
                    'cli_id' => (int) $c->cli_id,
                    'nombre' => $nombre,
                    'telefono' => (string) ($c->cli_telefono ?? ''),
                    'email' => (string) ($c->cli_email ?? ''),
                    'rfc' => (string) ($c->cli_rfc ?? ''),
                    'descuento_default' => $c->cli_descuento_default !== null ? (int) $c->cli_descuento_default : null,
                ];
            })
            ->values();

        return response()->json(['data' => $clientes]);
    }

    public function cobrar(StorePosVentaRequest $request): JsonResponse
    {
        $venta = $this->posVentaService->cobrar($request, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Venta cobrada correctamente.',
            'data' => [
                'psv_id' => $venta->psv_id,
                'psv_folio' => $venta->psv_folio,
                'psv_total' => (float) $venta->psv_total,
            ],
        ]);
    }

    public function registrarCambio(StorePosCambioRequest $request): JsonResponse
    {
        $venta = $this->posCambioVentaService->registrar($request, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Cambio/devolución registrado correctamente.',
            'data' => [
                'psv_id' => $venta->psv_id,
                'psv_folio' => $venta->psv_folio,
                'psv_total' => (float) $venta->psv_total,
                'psv_credito_cambio' => (float) $venta->psv_credito_cambio,
            ],
        ]);
    }

    public function cancelarVenta(CancelPosVentaRequest $request, PosVenta $venta): JsonResponse
    {
        $venta = $this->posVentaCancelacionService->cancelar(
            $request,
            $request->user(),
            $venta,
            (string) $request->validated('motivo')
        );

        return response()->json([
            'message' => 'Venta cancelada correctamente.',
            'data' => [
                'psv_id' => (int) $venta->psv_id,
                'psv_folio' => (string) $venta->psv_folio,
                'psv_estatus' => (string) $venta->psv_estatus,
                'psv_cancelado_at' => optional($venta->psv_cancelado_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function buscarVentaPorFolio(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
        ]);

        $venta = $this->posCambioVentaService->obtenerVentaParaCambio((string) $data['folio']);
        if (!$venta) {
            return response()->json([
                'message' => 'No se encontró la venta solicitada.',
            ], 404);
        }

        return response()->json(['data' => $venta]);
    }

    public function showVenta(PosVenta $venta): JsonResponse
    {
        return response()->json([
            'data' => $this->posCambioVentaService->obtenerVentaPorId($venta),
        ]);
    }

    public function ventasDelDia(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sesion = $estado['sesion_activa'] ?? null;
        if (!$sesion) {
            return response()->json(['data' => []]);
        }

        $buscar = trim((string) $request->query('q', ''));
        $rows = PosVenta::query()
            ->where('psv_cse_id', (int) $sesion['cse_id'])
            ->whereDate('psv_fecha_cobro', now()->toDateString())
            ->when($buscar !== '', function ($q) use ($buscar): void {
                $q->where('psv_folio', 'like', "%{$buscar}%");
            })
            ->orderByDesc('psv_id')
            ->limit(100)
            ->get(['psv_id', 'psv_folio', 'psv_total', 'psv_fecha_cobro', 'psv_metodo_pago', 'psv_estatus', 'psv_tipo_operacion']);

        return response()->json([
            'data' => $rows->map(fn (PosVenta $v) => [
                'psv_id' => (int) $v->psv_id,
                'psv_folio' => (string) $v->psv_folio,
                'psv_total' => (float) $v->psv_total,
                'psv_fecha_cobro' => optional($v->psv_fecha_cobro)->format('Y-m-d H:i:s'),
                'psv_metodo_pago' => (string) ($v->psv_metodo_pago ?? ''),
                'psv_estatus' => (string) ($v->psv_estatus ?? ''),
                'psv_tipo_operacion' => (string) ($v->psv_tipo_operacion ?? 'venta'),
            ])->values(),
        ]);
    }

    public function pedidosPendientesCobro(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sesion = $estado['sesion_activa'] ?? null;
        if (!$sesion) {
            return response()->json(['data' => []]);
        }

        $buscar = trim((string) $request->query('q', ''));
        $rows = DB::table('tbl_pedidos_piso_pdp as pdp')
            ->leftJoin('tbl_sucursales_scl as scl', 'scl.scl_id', '=', 'pdp.pdp_scl_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'pdp.pdp_alm_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'pdp.pdp_usr_id')
            ->where('pdp.pdp_deleted', false)
            ->whereNull('pdp.pdp_deleted_at')
            ->where('pdp.pdp_estatus', 'pendiente_cobro')
            ->where('pdp.pdp_scl_id', (int) ($sesion['caja_scl_id'] ?? 0))
            ->when($buscar !== '', function ($q) use ($buscar): void {
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('pdp.pdp_folio', 'like', "%{$buscar}%")
                        ->orWhere('pdp.pdp_observaciones', 'like', "%{$buscar}%")
                        ->orWhere('alm.alm_nombre', 'like', "%{$buscar}%")
                        ->orWhere('usr.usr_nombre', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('pdp.pdp_id')
            ->limit(100)
            ->get([
                'pdp.pdp_id',
                'pdp.pdp_folio',
                'pdp.pdp_total',
                'pdp.pdp_fecha',
                'scl.scl_nombre as sucursal',
                'alm.alm_nombre as almacen',
                'usr.usr_nombre as vendedor',
            ]);

        return response()->json([
            'data' => $rows->map(fn ($r) => [
                'pdp_id' => (int) $r->pdp_id,
                'pdp_folio' => (string) $r->pdp_folio,
                'pdp_total' => (float) ($r->pdp_total ?? 0),
                'pdp_fecha' => $r->pdp_fecha ? (string) $r->pdp_fecha : null,
                'sucursal' => (string) ($r->sucursal ?? ''),
                'almacen' => (string) ($r->almacen ?? ''),
                'vendedor' => (string) ($r->vendedor ?? ''),
            ])->values(),
        ]);
    }

    public function ticket(PosVenta $venta)
    {
        $venta->load([
            'almacen:alm_id,alm_nombre',
            'caja:caj_id,caj_nombre',
            'cajaSesion:cse_id,cse_caj_id',
            'cajaSesion.caja:caj_id,caj_nombre',
            'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
            'vendedor:usr_id,usr_nombre,usr_usuario',
            'ventaOrigen:psv_id,psv_folio',
            'detalle.sku:psk_id,psk_prd_id,psk_nombre',
            'detalle.sku.valoresAtributo:vat_id,vat_valor',
            'detalle.sku.producto:prd_id,prd_nombre,prd_mrc_id,prd_mdl_id,prd_lna_id,prd_ctg_id,prd_dsc_id',
            'detalle.sku.producto.marca:mrc_id,mrc_nombre',
            'detalle.sku.producto.modelo:mdl_id,mdl_nombre',
            'detalle.sku.producto.linea:lna_id,lna_nombre',
            'detalle.sku.producto.categoria:ctg_id,ctg_nombre',
            'detalle.sku.producto.descripcionCatalogo:dsc_id,dsc_nombre',
            'detalle.vendedor:usr_id,usr_nombre,usr_usuario',
            'cambioDevoluciones.sku:psk_id,psk_nombre',
        ]);
        $ticketConfig = PosTicketConfiguracion::query()->first();

        $lineas = max(1, (int) $venta->detalle->count());
        $altoBase = 165 + ($lineas * 12);
        $altoEncabezado = $ticketConfig?->ptc_texto_encabezado ? min(32, 8 + (int) ceil(mb_strlen((string) $ticketConfig->ptc_texto_encabezado) / 34) * 4) : 0;
        $altoPie = $ticketConfig?->ptc_texto_pie ? min(34, 8 + (int) ceil(mb_strlen((string) $ticketConfig->ptc_texto_pie) / 34) * 4) : 0;
        $altoLogo = $ticketConfig?->ptc_logo_path ? 42 : 0;
        $alto = max(185, min(620, $altoBase + $altoEncabezado + $altoPie + $altoLogo));

        $pdf = new \TCPDF('P', 'mm', [80, $alto], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana'));
        $pdf->SetAuthor((string) ($venta->psv_usr_id ?? 'POS'));
        $pdf->SetTitle('Ticket ' . $venta->psv_folio);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 4);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $fmt = static fn ($v) => number_format((float) $v, 2, '.', ',');
        $metodo = strtoupper((string) ($venta->psv_metodo_pago ?? ''));
        $tipoOperacion = (string) ($venta->psv_tipo_operacion ?? 'venta');
        $ticketBrand = 'Matriz Comitán';
        $almacenNombre = trim((string) ($venta->almacen?->alm_nombre ?? ''));
        $cajaNombre = trim((string) ($venta->caja?->caj_nombre ?? $venta->cajaSesion?->caja?->caj_nombre ?? ''));
        $cajeroNombre = trim((string) ($venta->vendedor?->usr_usuario ?: $venta->vendedor?->usr_nombre ?: ''));
        $fecha = optional($venta->psv_fecha_cobro)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $clienteNombre = trim((string) ($venta->cliente?->cli_razon_social
            ?: implode(' ', array_filter([
                $venta->cliente?->cli_nombre,
                $venta->cliente?->cli_apellido_paterno,
                $venta->cliente?->cli_apellido_materno,
            ]))));
        $articulosVendidos = (int) round($venta->detalle->sum(fn ($d) => (float) $d->pvd_cantidad));

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

        if ($ticketConfig?->ptc_logo_path) {
            $logoFile = storage_path('app/public/' . $ticketConfig->ptc_logo_path);
            if (is_file($logoFile) && $this->tcpdfCanRenderLogo($logoFile)) {
                $pdf->Image($logoFile, 9, 5, 62, 32, '', '', '', false, 300, '', false, false, 0, false, false, false);
                $pdf->Ln(26);
            }
        }

        $html = '<div style="text-align:center;font-size:12px;font-weight:bold;">' . e($ticketBrand) . '</div>';
        if ($almacenNombre !== '') {
            $html .= '<div style="text-align:center;font-size:8px;font-weight:bold;line-height:1;">' . e($almacenNombre) . '</div>';
        }
        if ($ticketConfig?->ptc_texto_encabezado) {
            $html .= '<div style="font-size:7px;line-height:1.5;margin-top:3px;text-align:center;">' . nl2br(e((string) $ticketConfig->ptc_texto_encabezado)) . '</div>';
        }
        $html .= '<div style="font-size:7px;margin-top:3px;">Fecha: ' . e($fecha) . '<br/>Método: ' . e($metodo) . '</div>';
        if ($tipoOperacion === 'cambio') {
            $html .= '<div style="font-size:7px;text-align:center;margin-top:2px;"><b>Cambio aplicado</b>';
            if ($venta->ventaOrigen?->psv_folio) {
                $html .= '<br/>Referencia: ' . e((string) $venta->ventaOrigen->psv_folio);
            }
            $html .= '</div>';
        }
        if ($venta->psv_estatus === 'cancelada') {
            $html .= '<div style="font-size:8px;text-align:center;margin-top:3px;color:#b42318;"><b>VENTA CANCELADA</b></div>';
        }
        $html .= '<hr/>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;margin-bottom:2px;">';
        $html .= '<tr><td width="34%"><b>Caja</b></td><td width="66%" align="right">' . e($cajaNombre !== '' ? $cajaNombre : 'Sin caja') . '</td></tr>';
        $html .= '<tr><td width="34%"><b>Cajero</b></td><td width="66%" align="right">' . e($cajeroNombre !== '' ? $cajeroNombre : 'Sin cajero') . '</td></tr>';
        $html .= '<tr><td width="34%"><b>Cliente</b></td><td width="66%" align="right">' . e($clienteNombre !== '' ? $clienteNombre : 'Público en general') . '</td></tr>';
        $html .= '<tr><td width="34%"><b>Artículos</b></td><td width="66%" align="right">' . e((string) $articulosVendidos) . '</td></tr>';
        $html .= '</table>';
        $html .= '<hr/>';
        $html .= '<table cellspacing="0" cellpadding="2" style="font-size:7px;width:100%;">';
        $html .= '<tr><th align="left" width="46%">Producto</th><th align="left" width="24%">Vendedor</th><th align="right" width="10%">Cant</th><th align="right" width="20%">Imp</th></tr>';
        foreach ($venta->detalle as $d) {
            $nombre = $this->nombreProductoTicket($d->sku);
            $vendedorLinea = trim((string) ($d->vendedor?->usr_usuario ?: $d->vendedor?->usr_nombre ?: ''));
            $vendedorMostrado = $vendedorLinea !== '' ? $vendedorLinea : ($almacenNombre !== '' ? $almacenNombre : '—');
            $html .= '<tr>';
            $html .= '<td width="46%">' . e($nombre) . '</td>';
            $html .= '<td width="24%">' . e($vendedorMostrado) . '</td>';
            $html .= '<td align="right" width="10%">' . e($fmt($d->pvd_cantidad)) . '</td>';
            $html .= '<td align="right" width="20%">$' . e($fmt($d->pvd_importe)) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        if ($venta->cambioDevoluciones->isNotEmpty()) {
            $html .= '<hr/>';
            $html .= '<div style="font-size:7px;font-weight:bold;">Productos devueltos</div>';
            $html .= '<table cellspacing="0" cellpadding="2" style="font-size:7px;width:100%;">';
            foreach ($venta->cambioDevoluciones as $devolucion) {
                $html .= '<tr>';
                $html .= '<td width="55%">' . e((string) ($devolucion->sku?->psk_nombre ?? 'Producto')) . '</td>';
                $html .= '<td width="20%" align="right">' . e($fmt($devolucion->pcd_cantidad)) . '</td>';
                $html .= '<td width="25%" align="right">$' . e($fmt($devolucion->pcd_importe_credito)) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        $html .= '<hr/>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:8px;width:100%;">';
        $html .= '<tr><td>Subtotal</td><td align="right">$' . e($fmt($venta->psv_subtotal)) . '</td></tr>';
        $html .= '<tr><td>Descuento</td><td align="right">$' . e($fmt($venta->psv_descuento)) . '</td></tr>';
        if ((float) $venta->psv_credito_cambio > 0) {
            $html .= '<tr><td>Crédito cambio</td><td align="right">-$' . e($fmt($venta->psv_credito_cambio)) . '</td></tr>';
        }
        $html .= '<tr><td><b>Total</b></td><td align="right"><b>$' . e($fmt($venta->psv_total)) . '</b></td></tr>';
        $html .= '<tr><td>Pagado</td><td align="right">$' . e($fmt($venta->psv_pagado)) . '</td></tr>';
        $html .= '<tr><td>Cambio</td><td align="right">$' . e($fmt($venta->psv_cambio)) . '</td></tr>';
        if ($venta->psv_estatus === 'cancelada') {
            $html .= '<tr><td>Motivo cancelación</td><td align="right">' . e((string) ($venta->psv_cancelacion_motivo ?? 'N/D')) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '<div style="text-align:center;font-size:7px;line-height:1.5;margin-top:5px;">'
            . nl2br(e((string) ($ticketConfig?->ptc_texto_pie ?: 'Gracias por su compra')))
            . '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $barcodeY = $pdf->GetY() + 2;
        $barcodeHeight = 10;
        $pdf->write1DBarcode(
            (string) $venta->psv_folio,
            'C128',
            8,
            $barcodeY,
            64,
            $barcodeHeight,
            0.33,
            $barcodeStyle,
            'N'
        );
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(4, $barcodeY + $barcodeHeight + 1);
        $pdf->Cell(72, 3.5, (string) $venta->psv_folio, 0, 1, 'C');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-' . $venta->psv_folio . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function ventasIndex()
    {
        $cajas = Caja::query()
            ->where('caj_deleted', false)
            ->whereNull('caj_deleted_at')
            ->orderBy('caj_nombre')
            ->get(['caj_id', 'caj_nombre']);

        $almacenes = Almacen::query()
            ->where('alm_deleted', false)
            ->whereNull('alm_deleted_at')
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_nombre')
            ->get(['alm_id', 'alm_nombre']);

        return view('operacion.ventas.index', compact('cajas', 'almacenes'));
    }

    public function ventasData(Request $request): JsonResponse
    {
        $rows = DB::table('tbl_pos_ventas_psv as psv')
            ->leftJoin('tbl_cajas_caj as caj', 'caj.caj_id', '=', 'psv.psv_caj_id')
            ->leftJoin('tbl_almacenes_alm as alm', 'alm.alm_id', '=', 'psv.psv_alm_id')
            ->leftJoin('tbl_usuarios_usr as usr', 'usr.usr_id', '=', 'psv.psv_usr_id')
            ->leftJoin('tbl_clientes_cli as cli', 'cli.cli_id', '=', 'psv.psv_cli_id')
            ->where('psv.psv_deleted', false)
            ->whereNull('psv.psv_deleted_at')
            ->when($request->filled('buscar'), function ($q) use ($request): void {
                $buscar = trim((string) $request->query('buscar'));
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('psv.psv_folio', 'like', "%{$buscar}%")
                        ->orWhere('usr.usr_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->when($request->filled('caja_id'), fn ($q) => $q->where('psv.psv_caj_id', (int) $request->query('caja_id')))
            ->when($request->filled('almacen_id'), fn ($q) => $q->where('psv.psv_alm_id', (int) $request->query('almacen_id')))
            ->when($request->filled('fecha_desde'), fn ($q) => $q->whereDate('psv.psv_fecha_cobro', '>=', (string) $request->query('fecha_desde')))
            ->when($request->filled('fecha_hasta'), fn ($q) => $q->whereDate('psv.psv_fecha_cobro', '<=', (string) $request->query('fecha_hasta')))
            ->orderByDesc('psv.psv_id')
            ->limit(500)
            ->get([
                'psv.psv_id',
                'psv.psv_folio',
                'psv.psv_fecha_cobro',
                'psv.psv_total',
                'psv.psv_metodo_pago',
                'psv.psv_estatus',
                'psv.psv_tipo_operacion',
                'caj.caj_nombre',
                'alm.alm_nombre',
                'usr.usr_nombre as vendedor',
                DB::raw("TRIM(CONCAT(COALESCE(cli.cli_nombre,''),' ',COALESCE(cli.cli_apellido_paterno,''),' ',COALESCE(cli.cli_apellido_materno,''))) as cliente"),
            ]);

        return response()->json(['data' => $rows]);
    }

    private function tcpdfCanRenderLogo(string $path): bool
    {
        if (!$this->isPngWithAlphaChannel($path)) {
            return true;
        }

        return extension_loaded('gd') || extension_loaded('imagick');
    }

    private function nombreProductoTicket(?ProductoSku $sku): string
    {
        $producto = $sku?->producto;
        $partes = [
            $producto?->prd_nombre,
            $producto?->marca?->mrc_nombre,
            $producto?->modelo?->mdl_nombre,
            $producto?->linea?->lna_nombre,
            $producto?->categoria?->ctg_nombre,
            $producto?->descripcionCatalogo?->dsc_nombre,
        ];
        $variantes = $sku?->valoresAtributo?->pluck('vat_valor')->all() ?? [];

        return collect(array_merge($partes, $variantes))
            ->map(fn ($valor) => trim((string) $valor))
            ->filter()
            ->implode(' ');
    }

    private function isPngWithAlphaChannel(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 26);
        fclose($handle);

        if (strlen($header) < 26 || substr($header, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return false;
        }

        $colorType = ord($header[25]);

        return in_array($colorType, [4, 6], true);
    }
}
