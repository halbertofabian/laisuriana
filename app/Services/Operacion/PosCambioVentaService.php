<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCambioVentaService
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly PosVentaService $posVentaService,
        private readonly InventarioBaseService $inventarioBaseService,
        private readonly AuditoriaService $auditoriaService,
        private readonly PosCreditoCambioService $posCreditoCambioService,
    ) {
    }

    public function registrar(Request $request, Usuario $usuario, array $datos): PosVenta
    {
        return DB::transaction(function () use ($request, $usuario, $datos): PosVenta {
            $estado = $this->posCajaSesionService->estadoUsuario($usuario);
            $sesion = $estado['sesion_activa'] ?? null;

            if (!$sesion) {
                throw ValidationException::withMessages([
                    'caja' => 'No tienes una sesión de caja activa para registrar el cambio.',
                ]);
            }

            $ventaOrigen = PosVenta::query()
                ->with([
                    'detalle.sku:psk_id,psk_nombre',
                    'ventaOrigen',
                ])
                ->lockForUpdate()
                ->findOrFail((int) $datos['venta_origen_id']);

            $this->validarVentaOrigen($ventaOrigen, $sesion);

            $devoluciones = $this->resolverDevoluciones($ventaOrigen, $datos['devoluciones'] ?? []);
            $creditoCambio = round((float) $devoluciones->sum('importe_credito'), 2);

            $preparacion = $this->posVentaService->prepararDetalleVenta($datos);
            $detalleSalida = $preparacion['detalle'];
            $subtotal = $preparacion['subtotal'];
            $descuentoGlobalMonto = $this->posVentaService->calcularDescuentoGlobal($subtotal, (float) ($datos['descuento_global'] ?? 0));
            $totalNuevoCarrito = $this->posVentaService->calcularTotalVenta($subtotal, $descuentoGlobalMonto);

            if ($totalNuevoCarrito < $creditoCambio) {
                throw ValidationException::withMessages([
                    'items' => 'El nuevo carrito debe tener un valor igual o mayor al producto devuelto.',
                ]);
            }

            $totalDiferencia = round($totalNuevoCarrito - $creditoCambio, 2);
            $montoEfectivo = round((float) ($datos['monto_efectivo'] ?? 0), 2);
            $montoTarjeta = round((float) ($datos['monto_tarjeta'] ?? 0), 2);
            $pagado = round($montoEfectivo + $montoTarjeta, 2);
            if ($totalDiferencia > 0 && $pagado < $totalDiferencia) {
                throw ValidationException::withMessages([
                    'pago' => 'El monto pagado no cubre la diferencia del cambio.',
                ]);
            }
            if ($totalDiferencia === 0.0 && $pagado > 0) {
                throw ValidationException::withMessages([
                    'pago' => 'Este cambio no requiere pago adicional.',
                ]);
            }
            $cambio = round(max(0, $pagado - $totalDiferencia), 2);

            $almacenId = (int) ($datos['almacen_id'] ?? 0);
            $sucursalId = (int) ($sesion['caja_scl_id'] ?? 0);
            if ($sucursalId <= 0) {
                throw ValidationException::withMessages([
                    'caja' => 'No se pudo identificar la sucursal de la sesión de caja.',
                ]);
            }
            $this->validarAlmacenCambio($almacenId, $sucursalId);
            $this->posVentaService->validarDetalleContraAlmacen($detalleSalida, $sucursalId, $almacenId);

            $venta = PosVenta::query()->create([
                'psv_folio' => $this->posVentaService->crearFolio($almacenId),
                'psv_cse_id' => (int) $sesion['cse_id'],
                'psv_caj_id' => (int) $sesion['caja_id'],
                'psv_scl_id' => $sucursalId,
                'psv_alm_id' => $almacenId,
                'psv_usr_id' => (int) $usuario->usr_id,
                'psv_cli_id' => $ventaOrigen->psv_cli_id,
                'psv_tipo_operacion' => 'cambio',
                'psv_venta_origen_id' => (int) $ventaOrigen->psv_id,
                'psv_estatus' => 'cobrada',
                'psv_subtotal' => $subtotal,
                'psv_descuento' => $descuentoGlobalMonto,
                'psv_credito_cambio' => $creditoCambio,
                'psv_total' => $totalDiferencia,
                'psv_metodo_pago' => $totalDiferencia > 0 ? (string) $datos['metodo_pago'] : 'sin_pago',
                'psv_pago_detalle' => [
                    'efectivo' => $montoEfectivo,
                    'tarjeta' => $montoTarjeta,
                ],
                'psv_pagado' => $pagado,
                'psv_cambio' => $cambio,
                'psv_notas' => $datos['notas'] ?? null,
                'psv_fecha_cobro' => now(),
                'psv_created_by_usr_id' => (int) $usuario->usr_id,
                'psv_updated_by_usr_id' => (int) $usuario->usr_id,
            ]);

            foreach ($devoluciones as $devolucion) {
                $almacenEntradaId = $this->resolverAlmacenEntrada(
                    $ventaOrigen->psv_scl_id,
                    $ventaOrigen->psv_alm_id,
                    (string) $devolucion['condicion']
                );

                PosCambioDetalle::query()->create([
                    'pcd_psv_id' => $venta->psv_id,
                    'pcd_psv_origen_id' => $ventaOrigen->psv_id,
                    'pcd_pvd_origen_id' => $devolucion['detalle']->pvd_id,
                    'pcd_psk_id' => $devolucion['detalle']->pvd_psk_id,
                    'pcd_alm_id' => $almacenEntradaId,
                    'pcd_cantidad' => $devolucion['cantidad'],
                    'pcd_precio_unitario' => $devolucion['precio_unitario'],
                    'pcd_importe_credito' => $devolucion['importe_credito'],
                    'pcd_condicion' => $devolucion['condicion'],
                    'pcd_created_by_usr_id' => (int) $usuario->usr_id,
                    'pcd_updated_by_usr_id' => (int) $usuario->usr_id,
                ]);

                $this->inventarioBaseService->registrarEntrada($request, [
                    'min_psk_id' => $devolucion['detalle']->pvd_psk_id,
                    'min_scl_id' => (int) $ventaOrigen->psv_scl_id,
                    'min_alm_id' => $almacenEntradaId,
                    'min_cantidad' => $devolucion['cantidad'],
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_motivo_texto' => 'Entrada por cambio POS ' . $venta->psv_folio,
                    'min_documento_referencia' => $venta->psv_folio,
                    'min_precio_unitario' => $devolucion['precio_unitario'],
                    'min_subtotal_linea' => round($devolucion['cantidad'] * $devolucion['precio_unitario'], 2),
                    'min_descuento_linea' => 0,
                    'min_total_linea' => $devolucion['importe_credito'],
                    'min_observaciones' => 'Condición devolución: ' . $devolucion['condicion'],
                ]);
            }

            foreach ($detalleSalida as $linea) {
                PosVentaDetalle::query()->create([
                    'pvd_psv_id' => $venta->psv_id,
                    'pvd_psk_id' => $linea['psk_id'],
                    'pvd_cantidad' => $linea['cantidad'],
                    'pvd_precio_unitario' => $linea['precio'],
                    'pvd_descuento_porcentaje' => $linea['descuento_porcentaje'],
                    'pvd_descuento_importe' => $linea['descuento_importe'],
                    'pvd_importe' => $linea['importe'],
                    'pvd_usr_id' => $linea['usr_id'] > 0 ? $linea['usr_id'] : null,
                    'pvd_created_by_usr_id' => (int) $usuario->usr_id,
                    'pvd_updated_by_usr_id' => (int) $usuario->usr_id,
                ]);

                $this->inventarioBaseService->registrarSalida($request, [
                    'min_psk_id' => $linea['psk_id'],
                    'min_scl_id' => $sucursalId,
                    'min_alm_id' => $almacenId,
                    'min_cantidad' => $linea['cantidad'],
                    'min_documento_tipo' => 'venta_pos',
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_motivo_texto' => 'Salida por cambio POS ' . $venta->psv_folio,
                    'min_documento_referencia' => $venta->psv_folio,
                    'min_precio_unitario' => $linea['precio'],
                    'min_subtotal_linea' => round($linea['cantidad'] * $linea['precio'], 2),
                    'min_descuento_linea' => $linea['descuento_importe'],
                    'min_total_linea' => $linea['importe'],
                    'min_permitir_negativo' => true,
                ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.cambio.registrar',
                'tbl_pos_ventas_psv',
                (string) $venta->psv_id,
                [
                    'psv_folio' => $venta->psv_folio,
                    'psv_venta_origen_id' => $ventaOrigen->psv_id,
                    'psv_folio_origen' => $ventaOrigen->psv_folio,
                    'credito_cambio' => $creditoCambio,
                    'total_diferencia' => $totalDiferencia,
                    'devoluciones' => $devoluciones->count(),
                    'partidas_nuevas' => $detalleSalida->count(),
                ]
            );

            return $venta->fresh([
                'ventaOrigen',
                'detalle.sku:psk_id,psk_nombre',
                'cambioDevoluciones.detalleOrigen',
                'cambioDevoluciones.sku:psk_id,psk_nombre',
            ]);
        });
    }

    private function validarAlmacenCambio(int $almacenId, int $sucursalId): void
    {
        if ($almacenId <= 0) {
            throw ValidationException::withMessages([
                'almacen_id' => 'Debes seleccionar el almacén del ticket para registrar el cambio.',
            ]);
        }

        $valido = Almacen::query()
            ->where('alm_id', $almacenId)
            ->where('alm_scl_id', $sucursalId)
            ->where('alm_estatus', 'activo')
            ->where('alm_deleted', false)
            ->whereNull('alm_deleted_at')
            ->exists();

        if (!$valido) {
            throw ValidationException::withMessages([
                'almacen_id' => 'El almacén seleccionado no pertenece a la sucursal activa o no está disponible.',
            ]);
        }
    }

    public function obtenerVentaParaCambio(string $folio): ?array
    {
        $folio = trim($folio);
        if ($folio === '') {
            return null;
        }

        $venta = PosVenta::query()
            ->with([
                'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
                'detalle.sku:psk_id,psk_nombre,psk_codigo',
                'detalle.cambiosDevolucion.ventaCambio',
                'ventaOrigen',
            ])
            ->where('psv_folio', $folio)
            ->where('psv_deleted', false)
            ->whereNull('psv_deleted_at')
            ->first();

        if (!$venta) {
            return null;
        }

        return $this->mapVentaParaCambio($venta);
    }

    public function obtenerVentaPorId(PosVenta $venta): array
    {
        $venta->loadMissing([
            'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
            'detalle.sku:psk_id,psk_nombre,psk_codigo',
            'detalle.cambiosDevolucion.ventaCambio',
            'ventaOrigen',
            'cambioDevoluciones.detalleOrigen',
            'cambioDevoluciones.sku:psk_id,psk_nombre,psk_codigo',
            'canceladoPor:usr_id,usr_nombre,usr_usuario',
        ]);

        return $this->mapVentaParaCambio($venta);
    }

    private function validarVentaOrigen(PosVenta $venta, array $sesion): void
    {
        if ($venta->psv_estatus === 'cancelada' || $venta->psv_cancelado_at !== null) {
            throw ValidationException::withMessages([
                'venta_origen_id' => 'La venta original ya fue cancelada.',
            ]);
        }

        if ((string) $venta->psv_tipo_operacion !== 'venta') {
            throw ValidationException::withMessages([
                'venta_origen_id' => 'Solo se pueden generar cambios a partir de una venta normal.',
            ]);
        }

        if ((int) $venta->psv_scl_id !== (int) ($sesion['caja_scl_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'venta_origen_id' => 'La venta original pertenece a otra sucursal.',
            ]);
        }
    }

    private function resolverDevoluciones(PosVenta $venta, array $solicitadas): Collection
    {
        $detalles = $venta->detalle->keyBy('pvd_id');

        return collect($solicitadas)->map(function (array $linea) use ($detalles) {
            $detalleId = (int) ($linea['pvd_id'] ?? 0);
            $cantidad = round((float) ($linea['cantidad'] ?? 0), 2);
            $detalle = $detalles->get($detalleId);

            if (!$detalle) {
                throw ValidationException::withMessages([
                    'devoluciones' => 'Una de las partidas a devolver no pertenece a la venta original.',
                ]);
            }

            $devuelto = $this->cantidadDevueltaActiva($detalle->pvd_id);
            $disponible = round((float) $detalle->pvd_cantidad - $devuelto, 2);
            if ($cantidad > $disponible) {
                throw ValidationException::withMessages([
                    'devoluciones' => 'La cantidad a devolver excede lo disponible de la venta original.',
                ]);
            }

            $precioUnitario = round((float) $detalle->pvd_importe / max(0.01, (float) $detalle->pvd_cantidad), 2);

            return [
                'detalle' => $detalle,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'importe_credito' => round($cantidad * $precioUnitario, 2),
                // La UI conserva el selector, pero en backend toda devolución POS se trata como reventa.
                'condicion' => 'reventa',
            ];
        })->values();
    }

    private function cantidadDevueltaActiva(int $detalleVentaId): float
    {
        return $this->posCreditoCambioService->cantidadDevueltaActivaPorDetalle($detalleVentaId);
    }

    private function resolverAlmacenEntrada(int $sucursalId, int $almacenVentaId, string $condicion): int
    {
        if ($condicion === 'reventa') {
            return $almacenVentaId;
        }

        $almacenDevolucionesId = Almacen::query()
            ->join('tbl_tipos_almacen_tal as tal', 'tal.tal_id', '=', 'tbl_almacenes_alm.alm_tal_id')
            ->where('tbl_almacenes_alm.alm_scl_id', $sucursalId)
            ->where('tbl_almacenes_alm.alm_estatus', 'activo')
            ->where('tal.tal_clave', 'devoluciones')
            ->value('tbl_almacenes_alm.alm_id');

        return $almacenDevolucionesId ? (int) $almacenDevolucionesId : $almacenVentaId;
    }

    private function mapVentaParaCambio(PosVenta $venta): array
    {
        $clienteNombre = trim((string) ($venta->cliente?->cli_razon_social ?: implode(' ', array_filter([
            $venta->cliente?->cli_nombre,
            $venta->cliente?->cli_apellido_paterno,
            $venta->cliente?->cli_apellido_materno,
        ]))));

        return [
            'psv_id' => (int) $venta->psv_id,
            'psv_folio' => (string) $venta->psv_folio,
            'psv_tipo_operacion' => (string) ($venta->psv_tipo_operacion ?? 'venta'),
            'psv_estatus' => (string) ($venta->psv_estatus ?? ''),
            'psv_fecha_cobro' => optional($venta->psv_fecha_cobro)->format('Y-m-d H:i:s'),
            'psv_total' => (float) $venta->psv_total,
            'psv_subtotal' => (float) $venta->psv_subtotal,
            'psv_descuento' => (float) $venta->psv_descuento,
            'psv_credito_cambio' => (float) ($venta->psv_credito_cambio ?? 0),
            'psv_venta_origen_id' => $venta->psv_venta_origen_id ? (int) $venta->psv_venta_origen_id : null,
            'psv_venta_origen_folio' => $venta->ventaOrigen?->psv_folio,
            'psv_cancelado_at' => optional($venta->psv_cancelado_at)->format('Y-m-d H:i:s'),
            'psv_cancelacion_motivo' => $venta->psv_cancelacion_motivo,
            'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : 'Público general',
            'detalle' => $venta->detalle->map(function (PosVentaDetalle $detalle) {
                $devuelto = round((float) $detalle->cambiosDevolucion
                    ->filter(fn ($item) => $item->ventaCambio?->psv_estatus !== 'cancelada')
                    ->sum('pcd_cantidad'), 2);
                $disponible = round(max(0, (float) $detalle->pvd_cantidad - $devuelto), 2);
                $precioUnitario = round((float) $detalle->pvd_importe / max(0.01, (float) $detalle->pvd_cantidad), 2);

                return [
                    'pvd_id' => (int) $detalle->pvd_id,
                    'psk_id' => (int) $detalle->pvd_psk_id,
                    'psk_codigo' => (string) ($detalle->sku?->psk_codigo ?? ''),
                    'sku_nombre' => (string) ($detalle->sku?->psk_nombre ?? ''),
                    'cantidad' => (float) $detalle->pvd_cantidad,
                    'cantidad_devuelta' => $devuelto,
                    'cantidad_disponible' => $disponible,
                    'precio_unitario' => $precioUnitario,
                    'importe' => (float) $detalle->pvd_importe,
                ];
            })->values()->all(),
            'devoluciones' => $venta->cambioDevoluciones->map(fn (PosCambioDetalle $detalle) => [
                'pcd_id' => (int) $detalle->pcd_id,
                'pvd_origen_id' => (int) $detalle->pcd_pvd_origen_id,
                'psk_id' => (int) $detalle->pcd_psk_id,
                'psk_codigo' => (string) ($detalle->sku?->psk_codigo ?? ''),
                'sku_nombre' => (string) ($detalle->sku?->psk_nombre ?? ''),
                'cantidad' => (float) $detalle->pcd_cantidad,
                'importe_credito' => (float) $detalle->pcd_importe_credito,
                'condicion' => (string) $detalle->pcd_condicion,
            ])->values()->all(),
            'puede_cancelarse' => $venta->psv_estatus !== 'cancelada',
        ];
    }
}
