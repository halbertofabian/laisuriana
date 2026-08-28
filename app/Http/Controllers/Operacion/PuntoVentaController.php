<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\CancelPosVentaRequest;
use App\Http\Requests\Operacion\StorePosCajaMovimientoRequest;
use App\Http\Requests\Operacion\StorePosCreditoCambioRequest;
use App\Http\Requests\Operacion\StorePosCorteCajaRequest;
use App\Http\Requests\Operacion\StorePosVentaRequest;
use App\Http\Requests\Operacion\StorePosCambioRequest;
use App\Models\Almacen;
use App\Models\CajaMovimiento;
use App\Models\Cliente;
use App\Models\PosCreditoCambio;
use App\Models\PosCorteCaja;
use App\Models\PosVenta;
use App\Models\Caja;
use App\Models\PosTicketConfiguracion;
use App\Models\ProductoSku;
use App\Models\Usuario;
use App\Services\Operacion\PosCambioVentaService;
use App\Services\Operacion\PosCajaMovimientoService;
use App\Services\Operacion\PosCorteCajaService;
use App\Services\Operacion\PosCajaSesionService;
use App\Services\Operacion\PosCreditoCambioService;
use App\Services\Operacion\ProductoAlmacenResolverService;
use App\Services\Operacion\PosVentaCancelacionService;
use App\Services\Operacion\PosVentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuntoVentaController extends Controller
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly PosVentaService $posVentaService,
        private readonly PosVentaCancelacionService $posVentaCancelacionService,
        private readonly PosCambioVentaService $posCambioVentaService,
        private readonly PosCajaMovimientoService $posCajaMovimientoService,
        private readonly PosCorteCajaService $posCorteCajaService,
        private readonly PosCreditoCambioService $posCreditoCambioService,
        private readonly ProductoAlmacenResolverService $productoAlmacenResolverService,
    ) {
    }

    public function index(Request $request)
    {
        $usuario = $request->user();
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sucursalActivaId = (int) ($estado['sesion_activa']['caja_scl_id']
            ?? $usuario->sucursales()->orderByDesc('tbl_usuario_sucursales_usc.usc_es_predeterminada')->value('tbl_sucursales_scl.scl_id')
            ?? 0);
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
        $puedeRegistrarRetiroCaja = $usuario?->tienePermiso('pos.retiro_caja') ?? false;
        $puedeRegistrarGastoCaja = $usuario?->tienePermiso('pos.gasto_caja') ?? false;
        $usuariosAutorizadosCorte = $this->posCorteCajaService->usuariosAutorizados();
        $usuariosAutorizadosRetiro = Usuario::query()
            ->select('tbl_usuarios_usr.usr_id', 'tbl_usuarios_usr.usr_nombre', 'tbl_usuarios_usr.usr_usuario')
            ->join('tbl_usuario_roles_url as url', 'url.url_usr_id', '=', 'tbl_usuarios_usr.usr_id')
            ->join('tbl_rol_permisos_rpm as rpm', 'rpm.rpm_rol_id', '=', 'url.url_rol_id')
            ->join('tbl_permisos_prm as prm', 'prm.prm_id', '=', 'rpm.rpm_prm_id')
            ->where('prm.prm_clave', 'pos.retiro_caja')
            ->where('tbl_usuarios_usr.usr_estatus', 'activo')
            ->where('tbl_usuarios_usr.usr_deleted', false)
            ->whereNull('tbl_usuarios_usr.usr_deleted_at')
            ->where('url.url_estatus', 'activo')
            ->where('url.url_deleted', false)
            ->whereNull('url.url_deleted_at')
            ->where('rpm.rpm_estatus', 'activo')
            ->where('rpm.rpm_deleted', false)
            ->whereNull('rpm.rpm_deleted_at')
            ->where('prm.prm_estatus', 'activo')
            ->where('prm.prm_deleted', false)
            ->whereNull('prm.prm_deleted_at')
            ->distinct()
            ->orderBy('tbl_usuarios_usr.usr_nombre')
            ->get()
            ->map(fn (Usuario $u) => [
                'usr_id' => (int) $u->usr_id,
                'usr_nombre' => (string) $u->usr_nombre,
                'usr_usuario' => (string) $u->usr_usuario,
            ])
            ->values();
        $categoriasGastoSugeridas = CajaMovimiento::query()
            ->where('cjm_tipo', 'gasto')
            ->where('cjm_deleted', false)
            ->whereNull('cjm_deleted_at')
            ->where('cjm_estatus', 'registrado')
            ->whereNotNull('cjm_categoria')
            ->where('cjm_categoria', '!=', '')
            ->when($estado['sesion_activa']['caja_scl_id'] ?? null, fn ($query, $sucursalId) => $query->where('cjm_scl_id', (int) $sucursalId))
            ->orderByDesc('cjm_id')
            ->pluck('cjm_categoria')
            ->map(fn ($categoria) => trim((string) $categoria))
            ->filter()
            ->unique(fn ($categoria) => mb_strtolower($categoria))
            ->values();
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
            'sucursalActivaId',
            'caja',
            'estado',
            'almacenesVenta',
            'puedeCrearCliente',
            'puedeCancelarVenta',
            'puedeRegistrarCambio',
            'puedeRegistrarRetiroCaja',
            'puedeRegistrarGastoCaja',
            'usuariosAutorizadosCorte',
            'usuariosAutorizadosRetiro',
            'categoriasGastoSugeridas',
            'vendedores'
        ));
    }

    public function resolverProductoAlmacen(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'psk_id' => ['required', 'integer', 'exists:tbl_producto_skus_psk,psk_id'],
            'scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
        ]);

        $resultado = $this->productoAlmacenResolverService->resolverSkuAlmacen(
            (int) $datos['psk_id'],
            (int) $datos['scl_id']
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
            'scl_id' => ['required', 'integer', 'exists:tbl_sucursales_scl,scl_id'],
            'almacen_id' => ['required', 'integer', 'exists:tbl_almacenes_alm,alm_id'],
        ]);

        $resultado = $this->productoAlmacenResolverService->validarSkuParaAlmacen(
            (int) $datos['psk_id'],
            (int) $datos['scl_id'],
            (int) $datos['almacen_id']
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

    public function estadoCaja(Request $request): JsonResponse
    {
        $resumen = $this->posCajaMovimientoService->resumenSesionActual($request->user());

        return response()->json([
            'data' => $this->posCajaSesionService->estadoUsuario($request->user()),
            'resumen' => $resumen,
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

    public function generarCreditoCambio(StorePosCreditoCambioRequest $request): JsonResponse
    {
        $credito = $this->posCreditoCambioService->generar($request, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Crédito de cambio generado correctamente.',
            'data' => [
                'pcc_id' => (int) $credito->pcc_id,
                'pcc_folio' => (string) $credito->pcc_folio,
                'pcc_total_credito' => (float) $credito->pcc_total_credito,
                'pcc_saldo_disponible' => (float) $credito->pcc_saldo_disponible,
            ],
        ]);
    }

    public function buscarCreditoCambioPorFolio(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $estado = $this->posCajaSesionService->estadoUsuario($request->user());
        $sesion = $estado['sesion_activa'] ?? null;
        $sucursalId = (int) ($sesion['caja_scl_id']
            ?? $request->user()?->sucursales()->orderByDesc('tbl_usuario_sucursales_usc.usc_es_predeterminada')->value('tbl_sucursales_scl.scl_id')
            ?? 0);

        $credito = $this->posCreditoCambioService->buscarDisponiblePorFolio(
            (string) $data['folio'],
            $sucursalId,
            (float) ($data['total'] ?? 0)
        );

        if (!$credito) {
            return response()->json([
                'message' => 'No se encontró el crédito de cambio solicitado.',
            ], 404);
        }

        return response()->json(['data' => $credito]);
    }

    public function listarCreditosCambio(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folio' => ['nullable', 'string', 'max:50'],
            'cliente' => ['nullable', 'string', 'max:150'],
            'estatus' => ['nullable', 'string', 'in:disponible,parcial,aplicado,cancelado'],
        ]);

        $estado = $this->posCajaSesionService->estadoUsuario($request->user());
        $sesion = $estado['sesion_activa'] ?? null;
        $sucursalId = (int) ($sesion['caja_scl_id']
            ?? $request->user()?->sucursales()->orderByDesc('tbl_usuario_sucursales_usc.usc_es_predeterminada')->value('tbl_sucursales_scl.scl_id')
            ?? 0);

        return response()->json([
            'data' => $this->posCreditoCambioService->listarDisponiblesParaSucursal($sucursalId, $data),
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
        $resumenCaja = $this->posCajaMovimientoService->resumenSesionActual($usuario);
        if (!$sesion) {
            return response()->json([
                'data' => [],
                'resumen' => [
                    'inicio_caja' => 0,
                    'efectivo_ventas_neto' => 0,
                    'efectivo_disponible' => 0,
                    'umbral_retiro' => 0,
                    'retiro_recomendado' => false,
                    'excedente_umbral' => 0,
                    'ventas_del_dia' => 0,
                    'total_vendido' => 0,
                    'ventas_por_metodo' => [],
                    'credito_ventas' => 0,
                    'abono_credito' => 0,
                    'credito_cambios' => 0,
                    'cantidad_cambios' => 0,
                    'importe_cobrado_cambios' => 0,
                    'gastos' => 0,
                    'retiros' => 0,
                ],
            ]);
        }

        $buscar = trim((string) $request->query('q', ''));
        $rows = PosVenta::query()
            ->where('psv_cse_id', (int) $sesion['cse_id'])
            ->where('psv_deleted', false)
            ->whereNull('psv_deleted_at')
            ->where('psv_estatus', '!=', 'cancelada')
            ->when($buscar !== '', function ($q) use ($buscar): void {
                $q->where('psv_folio', 'like', "%{$buscar}%");
            })
            ->orderByDesc('psv_id')
            ->limit(100)
            ->get([
                'psv_id',
                'psv_folio',
                'psv_total',
                'psv_fecha_cobro',
                'psv_metodo_pago',
                'psv_pago_detalle',
                'psv_estatus',
                'psv_tipo_operacion',
                'psv_credito_cambio',
            ]);

        return response()->json([
            'data' => $rows->map(fn (PosVenta $v) => [
                'psv_id' => (int) $v->psv_id,
                'psv_folio' => (string) $v->psv_folio,
                'psv_total' => (float) $v->psv_total,
                'psv_fecha_cobro' => optional($v->psv_fecha_cobro)->format('Y-m-d H:i:s'),
                'psv_metodo_pago' => $this->resolverMetodoPago($v),
                'pagos' => $this->desglosePagos($v),
                'psv_estatus' => (string) ($v->psv_estatus ?? ''),
                'psv_tipo_operacion' => (string) ($v->psv_tipo_operacion ?? 'venta'),
                'psv_credito_cambio' => (float) ($v->psv_credito_cambio ?? 0),
            ])->values(),
            'resumen' => [
                'inicio_caja' => round((float) ($resumenCaja['inicio_caja'] ?? 0), 2),
                'efectivo_ventas_neto' => round((float) ($resumenCaja['efectivo_ventas_neto'] ?? 0), 2),
                'efectivo_disponible' => round((float) ($resumenCaja['efectivo_disponible'] ?? 0), 2),
                'umbral_retiro' => round((float) ($resumenCaja['umbral_retiro'] ?? 0), 2),
                'retiro_recomendado' => (bool) ($resumenCaja['retiro_recomendado'] ?? false),
                'excedente_umbral' => round((float) ($resumenCaja['excedente_umbral'] ?? 0), 2),
                'ventas_del_dia' => (int) ($resumenCaja['ventas_del_dia'] ?? $rows->count()),
                'total_vendido' => round((float) ($resumenCaja['total_vendido'] ?? 0), 2),
                'ventas_por_metodo' => $resumenCaja['ventas_por_metodo'] ?? [],
                'credito_ventas' => 0,
                'abono_credito' => 0,
                'credito_cambios' => round((float) ($resumenCaja['credito_cambios'] ?? 0), 2),
                'cantidad_cambios' => (int) ($resumenCaja['cantidad_cambios'] ?? 0),
                'importe_cobrado_cambios' => round((float) ($resumenCaja['importe_cobrado_cambios'] ?? 0), 2),
                'gastos' => round((float) ($resumenCaja['gastos'] ?? 0), 2),
                'retiros' => round((float) ($resumenCaja['retiros'] ?? 0), 2),
            ],
        ]);
    }

    private function resolverMetodoPago(PosVenta $venta): string
    {
        $metodo = (string) ($venta->psv_metodo_pago ?? '');

        // Compatibilidad visual con cambios registrados antes de identificar el monedero.
        if ($metodo === 'sin_pago' && (float) ($venta->psv_credito_cambio ?? 0) > 0) {
            return 'monedero_electronico';
        }

        return $metodo;
    }

    private function desglosePagos(PosVenta $venta): array
    {
        $detalle = is_array($venta->psv_pago_detalle) ? $venta->psv_pago_detalle : [];
        $metodo = $this->resolverMetodoPago($venta);
        $creditoCambio = round((float) ($venta->psv_credito_cambio ?? 0), 2);
        $efectivoRecibido = round((float) ($detalle['efectivo'] ?? 0), 2);
        $tarjeta = round((float) ($detalle['tarjeta'] ?? 0), 2);

        if ($efectivoRecibido <= 0 && $metodo === 'efectivo') {
            $efectivoRecibido = round((float) ($venta->psv_pagado ?? 0), 2);
        }

        $pagos = [];
        if ($creditoCambio > 0) {
            $pagos[] = ['clave' => 'monedero_electronico', 'monto' => $creditoCambio];
        }

        $efectivo = round(max(0, $efectivoRecibido - (float) ($venta->psv_cambio ?? 0)), 2);
        if ($efectivo > 0) {
            $pagos[] = ['clave' => 'efectivo', 'monto' => $efectivo];
        }
        if ($tarjeta > 0) {
            $pagos[] = ['clave' => 'tarjeta', 'monto' => $tarjeta];
        }

        return $pagos ?: [[
            'clave' => $metodo ?: 'sin_pago',
            'monto' => round((float) ($venta->psv_total ?? 0), 2),
        ]];
    }

    public function registrarRetiroCaja(StorePosCajaMovimientoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tipo'] = 'retiro';
        $movimiento = $this->posCajaMovimientoService->registrar($request, $request->user(), $payload);

        return response()->json([
            'message' => 'Retiro de caja registrado correctamente.',
            'data' => [
                'cjm_id' => $movimiento->cjm_id,
                'cjm_folio' => $movimiento->cjm_folio,
                'ticket_url' => route('pos.caja.movimientos.ticket', $movimiento),
            ],
        ]);
    }

    public function registrarGastoCaja(StorePosCajaMovimientoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tipo'] = 'gasto';
        $movimiento = $this->posCajaMovimientoService->registrar($request, $request->user(), $payload);

        return response()->json([
            'message' => 'Gasto de caja registrado correctamente.',
            'data' => [
                'cjm_id' => $movimiento->cjm_id,
                'cjm_folio' => $movimiento->cjm_folio,
                'ticket_url' => route('pos.caja.movimientos.ticket', $movimiento),
            ],
        ]);
    }

    public function realizarCorteCaja(StorePosCorteCajaRequest $request): JsonResponse
    {
        $corte = $this->posCorteCajaService->cerrar($request, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Corte de caja registrado correctamente.',
            'data' => [
                'pco_id' => (int) $corte->pco_id,
                'pco_folio' => (string) $corte->pco_folio,
                'pco_efectivo_esperado' => (float) $corte->pco_efectivo_esperado,
                'pco_efectivo_reportado' => (float) $corte->pco_efectivo_reportado,
                'pco_diferencia' => (float) $corte->pco_diferencia,
                'ticket_url' => route('pos.caja.cortes.ticket', $corte),
            ],
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
            'detalle.almacen:alm_id,alm_nombre',
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
            $almacenLinea = trim((string) ($d->almacen?->alm_nombre ?? ''));
            $vendedorMostrado = $vendedorLinea !== '' ? $vendedorLinea : ($almacenLinea !== '' ? $almacenLinea : ($almacenNombre !== '' ? $almacenNombre : '—'));
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

    public function ticketCreditoCambio(PosCreditoCambio $credito)
    {
        $credito->load([
            'almacen:alm_id,alm_nombre',
            'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
            'ventaOrigen:psv_id,psv_folio',
            'detalle.sku:psk_id,psk_nombre',
        ]);

        $ticketConfig = PosTicketConfiguracion::query()->first();
        $lineas = max(1, (int) $credito->detalle->count());
        $alto = max(170, min(500, 155 + ($lineas * 10)));

        $pdf = new \TCPDF('P', 'mm', [80, $alto], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana'));
        $pdf->SetAuthor((string) ($credito->pcc_usr_id ?? 'POS'));
        $pdf->SetTitle('Crédito ' . $credito->pcc_folio);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 4);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $fmt = static fn ($v) => number_format((float) $v, 2, '.', ',');
        $fecha = optional($credito->pcc_fecha_generado)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $clienteNombre = trim((string) ($credito->cliente?->cli_razon_social
            ?: implode(' ', array_filter([
                $credito->cliente?->cli_nombre,
                $credito->cliente?->cli_apellido_paterno,
                $credito->cliente?->cli_apellido_materno,
            ]))));

        $html = '<div style="text-align:center;font-size:12px;font-weight:bold;">Vale de cambio</div>';
        $html .= '<div style="text-align:center;font-size:8px;font-weight:bold;">' . e((string) ($credito->almacen?->alm_nombre ?? 'Sin almacén')) . '</div>';
        if ($ticketConfig?->ptc_texto_encabezado) {
            $html .= '<div style="font-size:7px;line-height:1.5;margin-top:3px;text-align:center;">' . nl2br(e((string) $ticketConfig->ptc_texto_encabezado)) . '</div>';
        }
        $html .= '<div style="font-size:7px;margin-top:4px;">Fecha: ' . e($fecha) . '</div>';
        $html .= '<div style="font-size:7px;">Folio venta origen: ' . e((string) ($credito->ventaOrigen?->psv_folio ?? 'N/D')) . '</div>';
        $html .= '<div style="font-size:7px;">Cliente: ' . e($clienteNombre !== '' ? $clienteNombre : 'Público general') . '</div>';
        $html .= '<hr/>';
        $html .= '<div style="font-size:7px;font-weight:bold;">Productos resguardados</div>';
        $html .= '<table cellspacing="0" cellpadding="2" style="font-size:7px;width:100%;">';
        foreach ($credito->detalle as $detalle) {
            $html .= '<tr>';
            $html .= '<td width="55%">' . e((string) ($detalle->sku?->psk_nombre ?? 'Producto')) . '</td>';
            $html .= '<td width="20%" align="right">' . e($fmt($detalle->pcdv_cantidad)) . '</td>';
            $html .= '<td width="25%" align="right">$' . e($fmt($detalle->pcdv_importe_credito)) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table><hr/>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:8px;width:100%;">';
        $html .= '<tr><td>Crédito generado</td><td align="right">$' . e($fmt($credito->pcc_total_credito)) . '</td></tr>';
        $html .= '<tr><td>Saldo disponible</td><td align="right"><b>$' . e($fmt($credito->pcc_saldo_disponible)) . '</b></td></tr>';
        $html .= '</table>';
        $html .= '<div style="text-align:center;font-size:7px;line-height:1.5;margin-top:5px;">Presenta este folio en caja para aplicar tu cambio.</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

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

        $barcodeY = $pdf->GetY() + 2;
        $pdf->write1DBarcode(
            (string) $credito->pcc_folio,
            'C128',
            8,
            $barcodeY,
            64,
            10,
            0.33,
            $barcodeStyle,
            'N'
        );
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(4, $barcodeY + 11);
        $pdf->Cell(72, 3.5, (string) $credito->pcc_folio, 0, 1, 'C');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="credito-cambio-' . $credito->pcc_folio . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function ticketEscpos(PosVenta $venta): JsonResponse
    {
        $venta->load([
            'almacen:alm_id,alm_nombre',
            'caja:caj_id,caj_nombre',
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
            'detalle.almacen:alm_id,alm_nombre',
            'cambioDevoluciones.sku:psk_id,psk_nombre',
        ]);

        $payload = $this->buildVentaEscposPayload($venta);

        return response()->json([
            'data' => [
                'source' => 'laisuriana-pos',
                'content_type' => 'application/vnd.escpos',
                'document_name' => 'ticket-venta-' . $venta->psv_folio . '.bin',
                'document_base64' => base64_encode($payload),
            ],
        ]);
    }

    public function ticketCreditoCambioEscpos(PosCreditoCambio $credito): JsonResponse
    {
        $credito->load([
            'almacen:alm_id,alm_nombre',
            'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
            'ventaOrigen:psv_id,psv_folio',
            'detalle.sku:psk_id,psk_nombre',
        ]);

        $payload = $this->buildCreditoCambioEscposPayload($credito);

        return response()->json([
            'data' => [
                'source' => 'laisuriana-pos',
                'content_type' => 'application/vnd.escpos',
                'document_name' => 'ticket-credito-cambio-' . $credito->pcc_folio . '.bin',
                'document_base64' => base64_encode($payload),
            ],
        ]);
    }

    public function ticketMovimientoCaja(CajaMovimiento $movimiento)
    {
        $movimiento->load([
            'caja:caj_id,caj_nombre',
            'cajaSesion:cse_id,cse_caj_id',
            'cajaSesion.caja:caj_id,caj_nombre',
            'cajero:usr_id,usr_nombre,usr_usuario',
            'autorizadoPor:usr_id,usr_nombre,usr_usuario',
        ]);

        $ticketConfig = PosTicketConfiguracion::query()->first();
        $copias = $movimiento->cjm_tipo === 'retiro'
            ? ['Copia cajero', 'Copia resguardo']
            : ['Comprobante de gasto'];
        $denominaciones = collect((array) $movimiento->cjm_denominaciones)
            ->filter(fn ($denominacion): bool => (int) ($denominacion['cantidad'] ?? 0) > 0)
            ->values();
        $alto = $movimiento->cjm_tipo === 'retiro'
            ? 255 + ($denominaciones->count() * 5)
            : 190;

        $pdf = new \TCPDF('P', 'mm', [80, $alto], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana'));
        $pdf->SetAuthor((string) ($movimiento->cjm_usr_cajero_id ?? 'POS'));
        $pdf->SetTitle('Movimiento ' . $movimiento->cjm_folio);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 4);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $fmt = static fn ($v) => number_format((float) $v, 2, '.', ',');
        $tipoLabel = $movimiento->cjm_tipo === 'retiro' ? 'Retiro de caja' : 'Gasto de caja';
        $cajaNombre = trim((string) ($movimiento->caja?->caj_nombre ?? $movimiento->cajaSesion?->caja?->caj_nombre ?? 'Sin caja'));
        $cajero = trim((string) ($movimiento->cajero?->usr_usuario ?: $movimiento->cajero?->usr_nombre ?: 'Sin cajero'));
        $autorizado = trim((string) ($movimiento->autorizadoPor?->usr_usuario ?: $movimiento->autorizadoPor?->usr_nombre ?: 'Sin autorización'));
        $fecha = optional($movimiento->cjm_fecha_movimiento)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');

        foreach ($copias as $index => $copiaLabel) {
            if ($index > 0) {
                $pdf->Ln(2);
                $pdf->writeHTML('<hr/>', true, false, true, false, '');
            }

            $html = '<div style="text-align:center;font-size:12px;font-weight:bold;">Matriz Comitán</div>';
            if ($ticketConfig?->ptc_texto_encabezado) {
                $html .= '<div style="font-size:7px;line-height:1.4;margin-top:3px;text-align:center;">' . nl2br(e((string) $ticketConfig->ptc_texto_encabezado)) . '</div>';
            }
            $html .= '<div style="text-align:center;font-size:8px;font-weight:bold;margin-top:3px;">' . e($tipoLabel) . '</div>';
            $html .= '<div style="text-align:center;font-size:7px;">' . e($copiaLabel) . '</div>';
            $html .= '<div style="font-size:7px;margin-top:3px;">Fecha: ' . e($fecha) . '<br/>Folio: ' . e((string) $movimiento->cjm_folio) . '</div>';
            $html .= '<hr/>';
            $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;">';
            $html .= '<tr><td width="38%"><b>Caja</b></td><td width="62%" align="right">' . e($cajaNombre) . '</td></tr>';
            $html .= '<tr><td width="38%"><b>Cajero</b></td><td width="62%" align="right">' . e($cajero) . '</td></tr>';
            $html .= '<tr><td width="38%"><b>Autoriza</b></td><td width="62%" align="right">' . e($autorizado) . '</td></tr>';
            if ($movimiento->cjm_categoria) {
                $html .= '<tr><td width="38%"><b>Categoría</b></td><td width="62%" align="right">' . e((string) $movimiento->cjm_categoria) . '</td></tr>';
            }
            if ($movimiento->cjm_referencia) {
                $html .= '<tr><td width="38%"><b>Referencia</b></td><td width="62%" align="right">' . e((string) $movimiento->cjm_referencia) . '</td></tr>';
            }
            $html .= '<tr><td width="38%"><b>Monto</b></td><td width="62%" align="right">$' . e($fmt($movimiento->cjm_monto)) . '</td></tr>';
            $html .= '</table>';
            if ($denominaciones->isNotEmpty()) {
                $html .= '<div style="font-size:7px;font-weight:bold;margin-top:3px;">DENOMINACIONES</div>';
                $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;">';
                foreach ($denominaciones as $denominacion) {
                    $cantidad = (int) ($denominacion['cantidad'] ?? 0);
                    $valor = (float) ($denominacion['valor'] ?? 0);
                    $etiqueta = (string) ($denominacion['etiqueta'] ?? ('$' . $fmt($valor)));
                    $html .= '<tr><td width="65%">' . e($etiqueta) . ' x ' . e((string) $cantidad) . '</td><td width="35%" align="right">$' . e($fmt($cantidad * $valor)) . '</td></tr>';
                }
                $html .= '</table>';
            }
            $html .= '<hr/>';
            $html .= '<div style="font-size:7px;"><b>Motivo</b><br/>' . nl2br(e((string) ($movimiento->cjm_motivo ?? 'Sin detalle'))) . '</div>';
            $html .= '<div style="text-align:center;font-size:7px;line-height:1.5;margin-top:6px;">'
                . nl2br(e((string) ($ticketConfig?->ptc_texto_pie ?: 'Movimiento registrado correctamente')))
                . '</div>';

            $pdf->writeHTML($html, true, false, true, false, '');
        }

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="movimiento-' . $movimiento->cjm_folio . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function ticketCorteCaja(PosCorteCaja $corte)
    {
        $corte->load([
            'caja:caj_id,caj_nombre',
            'sesion:cse_id,cse_caj_id,cse_monto_apertura,cse_abierta_at',
            'cajero:usr_id,usr_nombre,usr_usuario',
            'autorizadoPor:usr_id,usr_nombre,usr_usuario',
            'aperturaUsuario:usr_id,usr_nombre,usr_usuario',
            'denominaciones',
        ]);

        $ticketConfig = PosTicketConfiguracion::query()->first();
        $lineasDenominaciones = max(1, $corte->denominaciones->count());
        $alto = max(240, min(480, 220 + ($lineasDenominaciones * 10)));

        $pdf = new \TCPDF('P', 'mm', [80, $alto], true, 'UTF-8', false, false);
        $pdf->SetCreator(config('app.name', 'La Suriana'));
        $pdf->SetAuthor((string) ($corte->pco_usr_cajero_id ?? 'POS'));
        $pdf->SetTitle('Corte ' . $corte->pco_folio);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 4);
        $pdf->SetAutoPageBreak(true, 4);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $fmt = static fn ($v) => number_format((float) $v, 2, '.', ',');
        $cajaNombre = trim((string) ($corte->caja?->caj_nombre ?? 'Sin caja'));
        $cajero = trim((string) ($corte->cajero?->usr_usuario ?: $corte->cajero?->usr_nombre ?: 'Sin cajero'));
        $autorizado = trim((string) ($corte->autorizadoPor?->usr_usuario ?: $corte->autorizadoPor?->usr_nombre ?: 'Sin autorización'));
        $apertura = optional($corte->pco_abierta_at)->format('d/m/Y H:i') ?? 'N/D';
        $cierre = optional($corte->pco_cerrada_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $metodos = collect($corte->pco_resumen_metodos_pago ?? []);
        $resumenVentas = $corte->pco_resumen_ventas ?? [];

        $html = '<div style="text-align:center;font-size:12px;font-weight:bold;">Matriz Comitán</div>';
        if ($ticketConfig?->ptc_texto_encabezado) {
            $html .= '<div style="font-size:7px;line-height:1.4;margin-top:3px;text-align:center;">' . nl2br(e((string) $ticketConfig->ptc_texto_encabezado)) . '</div>';
        }
        $html .= '<div style="text-align:center;font-size:8px;font-weight:bold;margin-top:3px;">Corte de caja</div>';
        $html .= '<div style="font-size:7px;margin-top:3px;">Folio: ' . e((string) $corte->pco_folio) . '<br/>Cierre: ' . e($cierre) . '</div>';
        $html .= '<hr/>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;">';
        $html .= '<tr><td width="42%"><b>Caja</b></td><td width="58%" align="right">' . e($cajaNombre) . '</td></tr>';
        $html .= '<tr><td width="42%"><b>Cajero</b></td><td width="58%" align="right">' . e($cajero) . '</td></tr>';
        $html .= '<tr><td width="42%"><b>Autoriza</b></td><td width="58%" align="right">' . e($autorizado) . '</td></tr>';
        $html .= '<tr><td width="42%"><b>Apertura</b></td><td width="58%" align="right">' . e($apertura) . '</td></tr>';
        $html .= '</table>';
        $html .= '<hr/>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;">';
        $html .= '<tr><td>Total vendido</td><td align="right">$' . e($fmt($corte->pco_total_ventas)) . '</td></tr>';
        $html .= '<tr><td>Ventas efectivo</td><td align="right">$' . e($fmt((float) ($resumenVentas['efectivo_ventas_neto'] ?? 0))) . '</td></tr>';
        foreach ($metodos as $metodo) {
            $html .= '<tr><td>' . e((string) ($metodo['label'] ?? 'Método')) . '</td><td align="right">$' . e($fmt((float) ($metodo['monto'] ?? 0))) . '</td></tr>';
        }
        $html .= '<tr><td>Retiros</td><td align="right">$' . e($fmt($corte->pco_total_retiros)) . '</td></tr>';
        $html .= '<tr><td>Gastos</td><td align="right">$' . e($fmt($corte->pco_total_gastos)) . '</td></tr>';
        $html .= '<tr><td><b>Esperado</b></td><td align="right"><b>$' . e($fmt($corte->pco_efectivo_esperado)) . '</b></td></tr>';
        $html .= '<tr><td><b>Reportado</b></td><td align="right"><b>$' . e($fmt($corte->pco_efectivo_reportado)) . '</b></td></tr>';
        $html .= '<tr><td><b>Diferencia</b></td><td align="right"><b>$' . e($fmt($corte->pco_diferencia)) . '</b></td></tr>';
        $html .= '</table>';
        $html .= '<hr/>';
        $html .= '<div style="font-size:7px;font-weight:bold;">Denominaciones</div>';
        $html .= '<table cellspacing="0" cellpadding="1" style="font-size:7px;width:100%;">';
        foreach ($corte->denominaciones as $denominacion) {
            if ($denominacion->pdn_clave === 'cambio') {
                $html .= '<tr><td width="65%">Cambio</td><td width="35%" align="right">$' . e($fmt($denominacion->pdn_monto)) . '</td></tr>';
                continue;
            }

            $html .= '<tr><td width="65%">' . e((string) $denominacion->pdn_etiqueta) . ' x ' . e((string) ((int) $denominacion->pdn_cantidad_piezas)) . '</td><td width="35%" align="right">$' . e($fmt($denominacion->pdn_monto)) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '<div style="font-size:7px;text-align:center;margin-top:12px;">____________________________<br/>Firma de conformidad</div>';
        if ($corte->pco_observaciones) {
            $html .= '<div style="font-size:7px;margin-top:6px;"><b>Observaciones</b><br/>' . nl2br(e((string) $corte->pco_observaciones)) . '</div>';
        }
        $html .= '<div style="text-align:center;font-size:7px;line-height:1.5;margin-top:6px;">'
            . nl2br(e((string) ($ticketConfig?->ptc_texto_pie ?: 'Corte registrado correctamente')))
            . '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="corte-' . $corte->pco_folio . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function ticketCorteCajaEscpos(PosCorteCaja $corte): JsonResponse
    {
        $corte->load([
            'caja:caj_id,caj_nombre',
            'sesion:cse_id,cse_caj_id',
            'sesion.caja:caj_id,caj_nombre',
            'cajero:usr_id,usr_nombre,usr_usuario',
            'autorizadoPor:usr_id,usr_nombre,usr_usuario',
        ]);

        $payload = $this->buildCorteCajaEscposPayload($corte);

        return response()->json([
            'data' => [
                'source' => 'laisuriana-pos',
                'content_type' => 'application/vnd.escpos',
                'document_name' => 'ticket-corte-caja-' . $corte->pco_folio . '.bin',
                'document_base64' => base64_encode($payload),
            ],
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
            ->leftJoin('tbl_clientes_cli as cli', 'cli.cli_id', '=', 'psv.psv_cli_id')
            ->where('psv.psv_deleted', false)
            ->whereNull('psv.psv_deleted_at')
            ->when($request->filled('buscar'), function ($q) use ($request): void {
                $buscar = trim((string) $request->query('buscar'));
                $q->where(function ($sub) use ($buscar): void {
                    $sub->where('psv.psv_folio', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_nombre', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('cli.cli_apellido_materno', 'like', "%{$buscar}%");
                    app(\App\Services\Operacion\VentaListadoVendedoresService::class)
                        ->agregarFiltroBusqueda($sub, $buscar);
                });
            })
            ->when($request->filled('caja_id'), fn ($q) => $q->where('psv.psv_caj_id', (int) $request->query('caja_id')))
            ->when($request->filled('almacen_id'), function ($q) use ($request): void {
                $almacenId = (int) $request->query('almacen_id');

                $q->where(function ($sub) use ($almacenId): void {
                    // La cabecera conserva el almacén con el que se inició la venta para
                    // compatibilidad histórica; los detalles son la fuente de verdad.
                    $sub->where('psv.psv_alm_id', $almacenId)
                        ->orWhereExists(function ($detalle) use ($almacenId): void {
                            $detalle->selectRaw('1')
                                ->from('tbl_pos_venta_detalle_pvd as pvd_alm')
                                ->whereColumn('pvd_alm.pvd_psv_id', 'psv.psv_id')
                                ->where('pvd_alm.pvd_alm_id', $almacenId)
                                ->where('pvd_alm.pvd_deleted', false)
                                ->whereNull('pvd_alm.pvd_deleted_at');
                        });
                });
            })
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
                DB::raw("TRIM(CONCAT(COALESCE(cli.cli_nombre,''),' ',COALESCE(cli.cli_apellido_paterno,''),' ',COALESCE(cli.cli_apellido_materno,''))) as cliente"),
            ]);

        $almacenesPorVenta = collect();
        if ($rows->isNotEmpty()) {
            $almacenesPorVenta = DB::table('tbl_pos_venta_detalle_pvd as pvd')
                ->join('tbl_almacenes_alm as alm_detalle', 'alm_detalle.alm_id', '=', 'pvd.pvd_alm_id')
                ->whereIn('pvd.pvd_psv_id', $rows->pluck('psv_id'))
                ->where('pvd.pvd_deleted', false)
                ->whereNull('pvd.pvd_deleted_at')
                ->orderBy('alm_detalle.alm_nombre')
                ->get(['pvd.pvd_psv_id', 'pvd.pvd_alm_id', 'alm_detalle.alm_nombre'])
                ->groupBy('pvd_psv_id')
                ->map(fn ($detalles) => $detalles
                    ->unique('pvd_alm_id')
                    ->pluck('alm_nombre')
                    ->values());
        }

        $rows->each(function ($venta) use ($almacenesPorVenta): void {
            $almacenes = $almacenesPorVenta->get($venta->psv_id, collect());
            $venta->almacenes_involucrados = $almacenes->isNotEmpty()
                ? $almacenes->implode(' · ')
                : ($venta->alm_nombre ?: '—');
        });

        $rows = app(\App\Services\Operacion\VentaListadoVendedoresService::class)->agregar($rows);

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

    private function buildVentaEscposPayload(PosVenta $venta): string
    {
        $ticketConfig = PosTicketConfiguracion::query()->first();
        $width = 42;
        $lf = "\n";
        $init = "\x1B@\x1Ba\x00";
        $center = "\x1Ba\x01";
        $left = "\x1Ba\x00";
        $boldOn = "\x1BE\x01";
        $boldOff = "\x1BE\x00";
        $cut = "\n\n\n\x1DV\x00";
        $sep = str_repeat('-', $width);
        $totalSep = str_repeat('=', $width);

        $metodo = strtoupper((string) ($venta->psv_metodo_pago ?? ''));
        $tipoOperacion = (string) ($venta->psv_tipo_operacion ?? 'venta');
        $almacenNombre = $this->thermalAscii((string) ($venta->almacen?->alm_nombre ?? ''));
        $cajaNombre = $this->thermalAscii((string) ($venta->caja?->caj_nombre ?? $venta->cajaSesion?->caja?->caj_nombre ?? 'Sin caja'));
        $cajeroNombre = $this->thermalAscii((string) ($venta->vendedor?->usr_usuario ?: $venta->vendedor?->usr_nombre ?: 'Sin cajero'));
        $fecha = optional($venta->psv_fecha_cobro)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $clienteNombre = trim((string) ($venta->cliente?->cli_razon_social
            ?: implode(' ', array_filter([
                $venta->cliente?->cli_nombre,
                $venta->cliente?->cli_apellido_paterno,
                $venta->cliente?->cli_apellido_materno,
            ]))));
        $clienteNombre = $this->thermalAscii($clienteNombre !== '' ? $clienteNombre : 'Publico general');
        $articulosVendidos = (int) round($venta->detalle->sum(fn ($d) => (float) $d->pvd_cantidad));

        $p = $init;
        $p .= $center . $boldOn . 'LA SURIANA' . $lf . $boldOff;
        $p .= $center . 'Ticket de venta' . $lf;
        if ($almacenNombre !== '') {
            $p .= $center . $almacenNombre . $lf;
        }
        $p .= $left . $sep . $lf;
        if ($ticketConfig?->ptc_texto_encabezado) {
            foreach ($this->wrapEscpos((string) $ticketConfig->ptc_texto_encabezado, $width) as $line) {
                $p .= $center . $line . $lf;
            }
            $p .= $left;
        }
        $p .= $boldOn . $this->escposTcRow('Folio: ' . $this->thermalAscii((string) $venta->psv_folio), $fecha, $width) . $lf . $boldOff;
        $p .= $this->escposTcRow('Caja', $cajaNombre, $width) . $lf;
        $p .= $this->escposTcRow('Cajero', $cajeroNombre, $width) . $lf;
        $p .= $this->escposTcRow('Cliente', $clienteNombre, $width) . $lf;
        $p .= $this->escposTcRow('Metodo', $this->thermalAscii($metodo !== '' ? $metodo : 'N/D'), $width) . $lf;
        $p .= $this->escposTcRow('Articulos', (string) $articulosVendidos, $width) . $lf;
        if ($tipoOperacion === 'cambio' && $venta->ventaOrigen?->psv_folio) {
            $p .= 'Ref cambio: ' . $this->thermalAscii((string) $venta->ventaOrigen->psv_folio) . $lf;
        }
        if ($venta->psv_estatus === 'cancelada') {
            $p .= $center . $boldOn . 'VENTA CANCELADA' . $lf . $boldOff . $left;
        }
        $p .= $sep . $lf;
        $p .= $boldOn
            . $this->escposColumns([
                ['text' => 'PRODUCTO', 'width' => 18],
                ['text' => '', 'width' => 1],
                ['text' => 'VEND', 'width' => 7],
                ['text' => '', 'width' => 1],
                ['text' => 'CANT', 'width' => 6, 'align' => STR_PAD_LEFT],
                ['text' => '', 'width' => 1],
                ['text' => 'IMP', 'width' => 8, 'align' => STR_PAD_LEFT],
            ])
            . $lf
            . $boldOff;
        $p .= $sep . $lf;

        $detalleTotal = $venta->detalle->count();
        foreach ($venta->detalle as $index => $d) {
            $nombre = $this->thermalAscii($this->nombreProductoTicket($d->sku));
            $vendedorLinea = $this->thermalAscii((string) ($d->vendedor?->usr_usuario ?: $d->vendedor?->usr_nombre ?: ''));
            $almacenLinea = $this->thermalAscii((string) ($d->almacen?->alm_nombre ?? ''));
            $meta = trim($vendedorLinea) !== '' ? $vendedorLinea : ($almacenLinea !== '' ? $almacenLinea : $almacenNombre);
            $qty = number_format((float) $d->pvd_cantidad, 2, '.', ',');
            $importe = '$' . number_format((float) $d->pvd_importe, 2, '.', ',');
            $nombreLines = $this->wrapEscpos($nombre, 18);
            $metaLines = $this->wrapEscpos($meta, 7);
            $rowCount = max(count($nombreLines), count($metaLines));

            for ($i = 0; $i < $rowCount; $i++) {
                $p .= $this->escposColumns([
                    ['text' => $nombreLines[$i] ?? '', 'width' => 18],
                    ['text' => '', 'width' => 1],
                    ['text' => $metaLines[$i] ?? '', 'width' => 7],
                    ['text' => '', 'width' => 1],
                    ['text' => $i === 0 ? $qty : '', 'width' => 6, 'align' => STR_PAD_LEFT],
                    ['text' => '', 'width' => 1],
                    ['text' => $i === 0 ? $importe : '', 'width' => 8, 'align' => STR_PAD_LEFT],
                ]) . $lf;
            }
            if (($index + 1) < $detalleTotal) {
                $p .= str_repeat('-', $width) . $lf;
            }
        }

        if ($venta->cambioDevoluciones->isNotEmpty()) {
            $p .= $sep . $lf;
            $p .= $boldOn . 'DEVOLUCIONES' . $lf . $boldOff;
            foreach ($venta->cambioDevoluciones as $devolucion) {
                $nombre = $this->thermalAscii((string) ($devolucion->sku?->psk_nombre ?? 'Producto'));
                foreach ($this->wrapEscpos($nombre, $width) as $line) {
                    $p .= $line . $lf;
                }
                $p .= $this->escposTcRow(
                    '  Cant ' . number_format((float) $devolucion->pcd_cantidad, 2, '.', ','),
                    '$' . number_format((float) $devolucion->pcd_importe_credito, 2, '.', ','),
                    $width
                ) . $lf;
            }
        }

        $p .= $sep . $lf;
        $p .= $this->escposTcRow('Subtotal', '$' . number_format((float) $venta->psv_subtotal, 2, '.', ','), $width) . $lf;
        $p .= $this->escposTcRow('Descuento', '$' . number_format((float) $venta->psv_descuento, 2, '.', ','), $width) . $lf;
        if ((float) $venta->psv_credito_cambio > 0) {
            $p .= $this->escposTcRow('Credito cambio', '-$' . number_format((float) $venta->psv_credito_cambio, 2, '.', ','), $width) . $lf;
        }
        $p .= $totalSep . $lf;
        $p .= $boldOn . $this->escposTcRow('TOTAL', '$' . number_format((float) $venta->psv_total, 2, '.', ','), $width) . $lf . $boldOff;
        $p .= $this->escposTcRow('Pagado', '$' . number_format((float) $venta->psv_pagado, 2, '.', ','), $width) . $lf;
        $p .= $this->escposTcRow('Cambio', '$' . number_format((float) $venta->psv_cambio, 2, '.', ','), $width) . $lf;
        if ($venta->psv_notas) {
            $p .= $sep . $lf;
            foreach ($this->wrapEscpos('Notas: ' . (string) $venta->psv_notas, $width) as $line) {
                $p .= $line . $lf;
            }
        }
        $p .= $sep . $lf;
        $p .= $center;
        foreach ($this->wrapEscpos((string) ($ticketConfig?->ptc_texto_pie ?: 'Gracias por su compra'), $width) as $line) {
            $p .= $line . $lf;
        }
        $p .= $this->escposBarcodePayload((string) $venta->psv_folio);
        $p .= $cut;

        return $p;
    }

    private function buildCorteCajaEscposPayload(PosCorteCaja $corte): string
    {
        $width = 42;
        $lf = "\n";
        $init = "\x1B@\x1Ba\x00";
        $center = "\x1Ba\x01";
        $left = "\x1Ba\x00";
        $boldOn = "\x1BE\x01";
        $boldOff = "\x1BE\x00";
        $cut = "\n\n\n\x1DV\x00";
        $sep = str_repeat('-', $width);
        $totalSep = str_repeat('=', $width);

        $ticketConfig = PosTicketConfiguracion::query()->first();
        $fmt = static fn ($v) => number_format((float) $v, 2, '.', ',');
        $cajaNombre = $this->thermalAscii((string) ($corte->caja?->caj_nombre ?? $corte->sesion?->caja?->caj_nombre ?? 'Sin caja'));
        $cajero = $this->thermalAscii(trim((string) ($corte->cajero?->usr_usuario ?: $corte->cajero?->usr_nombre ?: 'Sin cajero')));
        $autorizado = $this->thermalAscii(trim((string) ($corte->autorizadoPor?->usr_usuario ?: $corte->autorizadoPor?->usr_nombre ?: 'Sin autorizacion')));
        $apertura = optional($corte->pco_abierta_at)->format('d/m/Y H:i') ?? 'N/D';
        $cierre = optional($corte->pco_cerrada_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $metodos = collect($corte->pco_resumen_metodos_pago ?? []);
        $resumenVentas = $corte->pco_resumen_ventas ?? [];

        $p = $init;
        $p .= $center . $boldOn . 'CORTE DE CAJA' . $lf . $boldOff;
        $p .= $center . $cajaNombre . $lf . $left;
        $p .= $sep . $lf;
        $p .= $boldOn . $this->escposTcRow('FOLIO: ' . $this->thermalAscii((string) $corte->pco_folio), $cierre, $width) . $lf . $boldOff;
        $p .= $this->escposTcRow('CAJA', $cajaNombre, $width) . $lf;
        $p .= $this->escposTcRow('CAJERO', $cajero, $width) . $lf;
        $p .= $this->escposTcRow('AUTORIZA', $autorizado, $width) . $lf;
        $p .= $this->escposTcRow('APERTURA', $this->thermalAscii($apertura), $width) . $lf;
        $p .= $sep . $lf;
        $p .= $this->escposTcRow('TOTAL VENDIDO', '$' . $fmt($corte->pco_total_ventas), $width) . $lf;
        if (!empty($resumenVentas['ventas_contado'])) {
            $p .= $this->escposTcRow('VENTA CONTADO', '$' . $fmt($resumenVentas['ventas_contado']), $width) . $lf;
        }
        if (!empty($resumenVentas['abonos_credito'])) {
            $p .= $this->escposTcRow('ABONOS CREDITO', '$' . $fmt($resumenVentas['abonos_credito']), $width) . $lf;
        }
        foreach ($metodos as $metodo) {
            $label = is_array($metodo) ? (string) ($metodo['label'] ?? $metodo['clave'] ?? 'METODO') : (string) $metodo;
            $monto = is_array($metodo) ? (float) ($metodo['monto'] ?? 0) : 0.0;
            $p .= $this->escposTcRow($this->thermalAscii($label), '$' . $fmt($monto), $width) . $lf;
        }
        $p .= $sep . $lf;
        $p .= $this->escposTcRow('RETIROS', '$' . $fmt($corte->pco_total_retiros), $width) . $lf;
        $p .= $this->escposTcRow('GASTOS', '$' . $fmt($corte->pco_total_gastos), $width) . $lf;
        $p .= $totalSep . $lf;
        $p .= $boldOn . $this->escposTcRow('ESPERADO', '$' . $fmt($corte->pco_efectivo_esperado), $width) . $lf . $boldOff;
        $p .= $boldOn . $this->escposTcRow('REPORTADO', '$' . $fmt($corte->pco_efectivo_reportado), $width) . $lf . $boldOff;
        $p .= $boldOn . $this->escposTcRow('DIFERENCIA', '$' . $fmt($corte->pco_diferencia), $width) . $lf . $boldOff;
        if ($corte->pco_observaciones) {
            $p .= $sep . $lf;
            foreach ($this->wrapEscpos('OBS: ' . (string) $corte->pco_observaciones, $width) as $line) {
                $p .= $line . $lf;
            }
        }
        $p .= $sep . $lf;
        $p .= $center;
        foreach ($this->wrapEscpos((string) ($ticketConfig?->ptc_texto_pie ?: 'CORTE REGISTRADO CORRECTAMENTE'), $width) as $line) {
            $p .= $line . $lf;
        }
        $p .= $this->escposBarcodePayload((string) $corte->pco_folio);
        $p .= $cut;

        return $p;
    }

    private function buildCreditoCambioEscposPayload(PosCreditoCambio $credito): string
    {
        $ticketConfig = PosTicketConfiguracion::query()->first();
        $width = 42;
        $lf = "\n";
        $init = "\x1B@\x1Ba\x00";
        $center = "\x1Ba\x01";
        $left = "\x1Ba\x00";
        $boldOn = "\x1BE\x01";
        $boldOff = "\x1BE\x00";
        $cut = "\n\n\n\x1DV\x00";
        $sep = str_repeat('-', $width);
        $totalSep = str_repeat('=', $width);

        $fecha = optional($credito->pcc_fecha_generado)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $almacen = $this->thermalAscii((string) ($credito->almacen?->alm_nombre ?? 'Sin almacen'));
        $cliente = trim((string) ($credito->cliente?->cli_razon_social
            ?: implode(' ', array_filter([
                $credito->cliente?->cli_nombre,
                $credito->cliente?->cli_apellido_paterno,
                $credito->cliente?->cli_apellido_materno,
            ]))));
        $cliente = $this->thermalAscii($cliente !== '' ? $cliente : 'Publico general');

        $p = $init;
        $p .= $center . $boldOn . 'VALE DE CAMBIO' . $lf . $boldOff;
        $p .= $center . $almacen . $lf . $left;
        $p .= $sep . $lf;
        $p .= $boldOn . $this->escposTcRow('Folio: ' . $this->thermalAscii((string) $credito->pcc_folio), $fecha, $width) . $lf . $boldOff;
        $p .= $this->escposTcRow('Cliente', $cliente, $width) . $lf;
        $p .= $this->escposTcRow('Venta origen', $this->thermalAscii((string) ($credito->ventaOrigen?->psv_folio ?? 'N/D')), $width) . $lf;
        $p .= $sep . $lf;

        foreach ($credito->detalle as $detalle) {
            $nombre = $this->thermalAscii((string) ($detalle->sku?->psk_nombre ?? 'Producto'));
            foreach ($this->wrapEscpos($nombre, $width) as $line) {
                $p .= $line . $lf;
            }
            $p .= $this->escposTcRow(
                '  Cant ' . number_format((float) $detalle->pcdv_cantidad, 2, '.', ','),
                '$' . number_format((float) $detalle->pcdv_importe_credito, 2, '.', ','),
                $width
            ) . $lf;
        }

        $p .= $totalSep . $lf;
        $p .= $boldOn . $this->escposTcRow('TOTAL', '$' . number_format((float) $credito->pcc_total_credito, 2, '.', ','), $width) . $lf . $boldOff;
        $p .= $this->escposTcRow('Saldo', '$' . number_format((float) $credito->pcc_saldo_disponible, 2, '.', ','), $width) . $lf;
        $p .= $sep . $lf;
        $p .= $center;
        foreach ($this->wrapEscpos((string) ($ticketConfig?->ptc_texto_pie ?: 'Conserve este vale para aplicarlo despues'), $width) as $line) {
            $p .= $line . $lf;
        }
        $p .= $this->escposBarcodePayload((string) $credito->pcc_folio);
        $p .= $cut;

        return $p;
    }

    private function wrapEscpos(string $text, int $width): array
    {
        $clean = $this->thermalAscii($text);
        if ($clean === '') {
            return [''];
        }

        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $clean) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                $lines[] = '';
                continue;
            }

            while (strlen($chunk) > $width) {
                $lines[] = rtrim(substr($chunk, 0, $width));
                $chunk = ltrim(substr($chunk, $width));
            }

            $lines[] = $chunk;
        }

        return $lines === [] ? [''] : $lines;
    }

    private function escposTcRow(string $left, string $right, int $width): string
    {
        $left = $this->thermalAscii($left);
        $right = $this->thermalAscii($right);
        $rightLen = strlen($right);
        $maxLeft = $width - $rightLen - 1;

        if (strlen($left) > $maxLeft) {
            $left = substr($left, 0, $maxLeft);
        }

        $spaces = $width - strlen($left) - $rightLen;

        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    private function escposColumns(array $columns): string
    {
        $line = '';

        foreach ($columns as $column) {
            $text = $this->thermalAscii((string) ($column['text'] ?? ''));
            $width = max(1, (int) ($column['width'] ?? 1));
            $align = (int) ($column['align'] ?? STR_PAD_RIGHT);

            if (strlen($text) > $width) {
                $text = substr($text, 0, $width);
            }

            $line .= str_pad($text, $width, ' ', $align);
        }

        return $line;
    }

    private function escposBarcodePayload(string $value): string
    {
        $barcode = $this->thermalAscii($value);
        if ($barcode === '') {
            return '';
        }

        $barcode = substr($barcode, 0, 120);
        $content = '{B' . $barcode;
        $length = strlen($content);

        return $this->escposAlign('C')
            . "\x1DH\x02"
            . "\x1Dw\x02"
            . "\x1Dh\x40"
            . "\x1DkI"
            . chr($length)
            . $content
            . "\n"
            . $this->escposAlign('L');
    }

    private function escposRasterFromImage($image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $widthBytes = (int) ceil($width / 8);
        $data = '';

        for ($y = 0; $y < $height; $y++) {
            for ($xb = 0; $xb < $widthBytes; $xb++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = ($xb * 8) + $bit;
                    if ($x >= $width) {
                        continue;
                    }

                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

                    if ($luma < 200) {
                        $byte |= (1 << (7 - $bit));
                    }
                }

                $data .= chr($byte);
            }
        }

        return "\x1D\x76\x30\x00"
            . chr($widthBytes & 0xFF)
            . chr(($widthBytes >> 8) & 0xFF)
            . chr($height & 0xFF)
            . chr(($height >> 8) & 0xFF)
            . $data;
    }

    private function escposAlign(string $align): string
    {
        return match (strtoupper($align)) {
            'C' => "\x1Ba\x01",
            'R' => "\x1Ba\x02",
            default => "\x1Ba\x00",
        };
    }

    private function thermalAscii(string $value): string
    {
        $ascii = Str::ascii($value);
        $clean = preg_replace('/[^\x20-\x7E]/', ' ', $ascii) ?? '';

        return Str::upper($clean);
    }
}
