<?php

namespace App\Services\Operacion;

use App\Models\PedidoPiso;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosVentaService
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly InventarioBaseService $inventarioBaseService,
        private readonly AuditoriaService $auditoriaService,
        private readonly LineaDescuentoService $lineaDescuentoService,
        private readonly ProductoAlmacenResolverService $productoAlmacenResolverService,
        private readonly PosCreditoCambioService $posCreditoCambioService,
    ) {
    }

    public function cobrar(Request $request, Usuario $usuario, array $datos): PosVenta
    {
        return DB::transaction(function () use ($request, $usuario, $datos): PosVenta {
            $estado = $this->posCajaSesionService->estadoUsuario($usuario);
            $sesion = $estado['sesion_activa'] ?? null;

            if (!$sesion) {
                throw ValidationException::withMessages([
                    'caja' => 'No tienes una sesión de caja activa para cobrar.',
                ]);
            }

            $almacenId = (int) $datos['almacen_id'];
            $sucursalId = (int) ($sesion['caja_scl_id'] ?? 0);
            if ($sucursalId <= 0) {
                throw ValidationException::withMessages([
                    'caja' => 'No se pudo identificar la sucursal de la sesión de caja.',
                ]);
            }

            $pedido = !empty($datos['pedido_id'])
                ? PedidoPiso::query()->find((int) $datos['pedido_id'])
                : null;
            $preparacion = $this->prepararDetalleVenta($datos);
            $detalle = $preparacion['detalle'];
            $this->validarDetalleContraAlmacen($detalle, $sucursalId, $almacenId);
            $subtotal = $preparacion['subtotal'];
            $descuentoGlobalMonto = $this->calcularDescuentoGlobal($subtotal, (float) ($datos['descuento_global'] ?? 0));
            $totalAntesCredito = $this->calcularTotalVenta($subtotal, $descuentoGlobalMonto);
            $creditoCambioFolio = trim((string) ($datos['credito_cambio_folio'] ?? ''));
            $creditoCambioMonto = 0.0;
            if ($creditoCambioFolio !== '') {
                $credito = $this->posCreditoCambioService->buscarDisponiblePorFolio($creditoCambioFolio, $sucursalId, $totalAntesCredito);
                if (!$credito) {
                    throw ValidationException::withMessages([
                        'credito_cambio_folio' => 'No se encontró el crédito de cambio indicado.',
                    ]);
                }
                if (!$credito['pcc_sucursal_valida']) {
                    throw ValidationException::withMessages([
                        'credito_cambio_folio' => 'El crédito de cambio pertenece a otra sucursal.',
                    ]);
                }
                if ((float) ($credito['pcc_saldo_disponible'] ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        'credito_cambio_folio' => 'El crédito de cambio ya no tiene saldo disponible.',
                    ]);
                }

                $creditoCambioMonto = round(min((float) $credito['pcc_saldo_disponible'], $totalAntesCredito), 2);
            }
            $total = round(max(0, $totalAntesCredito - $creditoCambioMonto), 2);
            $metodoPago = (string) ($datos['metodo_pago'] ?? 'efectivo');
            $montoEfectivo = round((float) ($datos['monto_efectivo'] ?? 0), 2);
            $montoTarjeta = round((float) ($datos['monto_tarjeta'] ?? 0), 2);
            $pagado = round($montoEfectivo + $montoTarjeta, 2);
            if ($pagado < $total) {
                throw ValidationException::withMessages([
                    'pago' => 'El monto pagado no cubre el total de la venta.',
                ]);
            }
            $cambio = round(max(0, $pagado - $total), 2);

            $venta = PosVenta::query()->create([
                'psv_folio' => $this->crearFolio($almacenId),
                'psv_cse_id' => (int) $sesion['cse_id'],
                'psv_caj_id' => (int) $sesion['caja_id'],
                'psv_scl_id' => $sucursalId,
                'psv_alm_id' => $almacenId,
                'psv_usr_id' => (int) $usuario->usr_id,
                'psv_cli_id' => !empty($datos['cliente_id']) ? (int) $datos['cliente_id'] : ($pedido?->pdp_cli_id ? (int) $pedido->pdp_cli_id : null),
                'psv_pdp_id' => !empty($datos['pedido_id']) ? (int) $datos['pedido_id'] : null,
                'psv_tipo_operacion' => 'venta',
                'psv_estatus' => 'cobrada',
                'psv_subtotal' => $subtotal,
                'psv_descuento' => $descuentoGlobalMonto,
                'psv_credito_cambio' => $creditoCambioMonto,
                'psv_total' => $total,
                'psv_metodo_pago' => $metodoPago,
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

            if ($creditoCambioFolio !== '' && $creditoCambioMonto > 0) {
                $this->posCreditoCambioService->aplicarEnVenta(
                    $request,
                    $usuario,
                    $venta,
                    $creditoCambioFolio,
                    $creditoCambioMonto
                );
            }

            foreach ($detalle as $linea) {
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

                // En POS sí permitimos negativos para reporte/corte posterior.
                $this->inventarioBaseService->registrarSalida($request, [
                    'min_psk_id' => $linea['psk_id'],
                    'min_scl_id' => $sucursalId,
                    'min_alm_id' => $almacenId,
                    'min_cantidad' => $linea['cantidad'],
                    'min_documento_tipo' => 'venta_pos',
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_motivo_texto' => 'Salida por venta POS ' . $venta->psv_folio,
                    'min_documento_referencia' => $venta->psv_folio,
                    'min_precio_unitario' => $linea['precio'],
                    'min_subtotal_linea' => round($linea['cantidad'] * $linea['precio'], 2),
                    'min_descuento_linea' => $linea['descuento_importe'],
                    'min_total_linea' => $linea['importe'],
                    'min_permitir_negativo' => true,
                ]);
            }

            if (!empty($datos['pedido_id'])) {
                PedidoPiso::query()
                    ->where('pdp_id', (int) $datos['pedido_id'])
                    ->update([
                        'pdp_estatus' => 'cobrado',
                        'pdp_updated_by_usr_id' => (int) $usuario->usr_id,
                    ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.venta.cobrar',
                'tbl_pos_ventas_psv',
                (string) $venta->psv_id,
                [
                    'psv_folio' => $venta->psv_folio,
                    'psv_alm_id' => $venta->psv_alm_id,
                    'psv_total' => $venta->psv_total,
                    'psv_credito_cambio' => $venta->psv_credito_cambio,
                    'partidas' => $detalle->count(),
                ]
            );

            return $venta;
        });
    }

    public function prepararDetalleVenta(array $datos): array
    {
        $skuIds = collect($datos['items'])
            ->pluck('psk_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();
        $skus = ProductoSku::query()->whereIn('psk_id', $skuIds)->get()->keyBy('psk_id');
        $pedidoDetalles = $this->pedidoDetallesParaVenta($datos);

        $subtotal = 0.0;
        $detalle = collect($datos['items'])->map(function ($item) use (&$subtotal, $skus, $pedidoDetalles) {
            $skuId = (int) $item['psk_id'];
            $cantidad = round((float) $item['cantidad'], 2);
            $sku = $skus->get($skuId);
            $precio = round((float) ($item['precio'] ?? $sku?->psk_precio ?? 0), 2);
            $configDescuento = $this->resolverDescuentoLineaVenta($item, $pedidoDetalles, $cantidad, $precio);
            $subtotal += $configDescuento['total'];

            return [
                'psk_id' => $skuId,
                'usr_id' => $this->resolverVendedorLinea($item, $pedidoDetalles),
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento_porcentaje' => $configDescuento['descuento_porcentaje_equivalente'],
                'descuento_importe' => $configDescuento['descuento_importe'],
                'importe' => $configDescuento['total'],
            ];
        })->values();

        return [
            'detalle' => $detalle,
            'subtotal' => round($subtotal, 2),
        ];
    }

    public function calcularDescuentoGlobal(float $subtotal, float $descuentoGlobalPct): float
    {
        $porcentaje = round($descuentoGlobalPct, 2);

        return round($subtotal * ($porcentaje / 100), 2);
    }

    public function calcularTotalVenta(float $subtotal, float $descuentoGlobalMonto): float
    {
        return round(max(0, $subtotal - $descuentoGlobalMonto), 2);
    }

    public function validarDetalleContraAlmacen(iterable $detalle, int $sucursalId, int $almacenId): void
    {
        foreach ($detalle as $linea) {
            $resultado = $this->productoAlmacenResolverService->validarSkuParaAlmacen(
                (int) ($linea['psk_id'] ?? 0),
                $sucursalId,
                $almacenId
            );

            if (!$resultado['valido']) {
                throw ValidationException::withMessages([
                    'items' => [$resultado['message']],
                ]);
            }
        }
    }

    public function crearFolio(int $almacenId): string
    {
        $prefix = 'VTA-' . str_pad((string) $almacenId, 3, '0', STR_PAD_LEFT) . '-';
        $last = PosVenta::query()
            ->where('psv_folio', 'like', $prefix . '%')
            ->orderByDesc('psv_id')
            ->value('psv_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function pedidoDetallesParaVenta(array $datos): array
    {
        if (empty($datos['pedido_id'])) {
            return [
                'por_id' => collect(),
                'por_sku' => collect(),
            ];
        }

        $detalles = DB::table('tbl_pedido_piso_detalle_ppd')
            ->where('ppd_pdp_id', (int) $datos['pedido_id'])
            ->where('ppd_deleted', false)
            ->whereNull('ppd_deleted_at')
            ->get([
                'ppd_id',
                'ppd_psk_id',
                'ppd_usr_id',
                'ppd_descuento_tipo',
                'ppd_descuento_valor',
                'ppd_descuento_importe',
            ]);

        return [
            'por_id' => $detalles->keyBy(fn ($d) => (int) $d->ppd_id),
            'por_sku' => $detalles->groupBy(fn ($d) => (int) $d->ppd_psk_id),
        ];
    }

    private function resolverVendedorLinea(array $item, array $pedidoDetalles): ?int
    {
        $origen = (string) ($item['origen'] ?? '');
        $pedidoDetalleId = (int) ($item['pedido_detalle_id'] ?? 0);
        $skuId = (int) ($item['psk_id'] ?? 0);

        if ($origen === 'pedido' || ($origen === '' && $pedidoDetalleId > 0)) {
            $detallePedido = $pedidoDetalleId > 0
                ? $pedidoDetalles['por_id']->get($pedidoDetalleId)
                : null;

            if (!$detallePedido && $skuId > 0) {
                $detallePedido = $pedidoDetalles['por_sku']->get($skuId)?->first();
            }

            if (!$detallePedido) {
                throw ValidationException::withMessages([
                    'items' => 'No fue posible validar el vendedor del pedido para una partida.',
                ]);
            }

            if ((int) ($detallePedido->ppd_psk_id ?? 0) !== $skuId) {
                throw ValidationException::withMessages([
                    'items' => 'Una partida no coincide con el detalle del pedido seleccionado.',
                ]);
            }

            $vendedorPedidoId = (int) ($detallePedido->ppd_usr_id ?? 0);
            return $vendedorPedidoId > 0 ? $vendedorPedidoId : null;
        }

        $vendedorLineaId = (int) ($item['usr_id'] ?? 0);
        return $vendedorLineaId > 0 ? $vendedorLineaId : null;
    }

    private function resolverDescuentoLineaVenta(array $item, array $pedidoDetalles, float $cantidad, float $precio): array
    {
        $descuentoTipo = (string) ($item['descuento_tipo'] ?? '');
        $descuentoValor = isset($item['descuento_valor'])
            ? (float) $item['descuento_valor']
            : (isset($item['descuento']) ? (float) $item['descuento'] : 0.0);

        if (($item['origen'] ?? '') === 'pedido' || (int) ($item['pedido_detalle_id'] ?? 0) > 0) {
            $detallePedido = null;
            $pedidoDetalleId = (int) ($item['pedido_detalle_id'] ?? 0);
            if ($pedidoDetalleId > 0) {
                $detallePedido = $pedidoDetalles['por_id']->get($pedidoDetalleId);
            }

            if (!$detallePedido) {
                $detallePedido = $pedidoDetalles['por_sku']->get((int) ($item['psk_id'] ?? 0))?->first();
            }

            if ($detallePedido && $descuentoTipo === '' && !isset($item['descuento_valor']) && !isset($item['descuento'])) {
                $descuentoTipo = (string) ($detallePedido->ppd_descuento_tipo ?? 'ninguno');
                $descuentoValor = (float) ($detallePedido->ppd_descuento_valor ?? 0);
            }
        }

        try {
            return $this->lineaDescuentoService->resolver($cantidad, $precio, $descuentoTipo, $descuentoValor);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }
    }
}
