<?php

namespace App\Services\Operacion;

use App\Models\Almacen;
use App\Models\PosCambioDetalle;
use App\Models\PosCreditoCambio;
use App\Models\PosCreditoCambioAplicacion;
use App\Models\PosCreditoCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCreditoCambioService
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly InventarioBaseService $inventarioBaseService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function generar(Request $request, Usuario $usuario, array $datos): PosCreditoCambio
    {
        return DB::transaction(function () use ($request, $usuario, $datos): PosCreditoCambio {
            $estado = $this->posCajaSesionService->estadoUsuario($usuario);
            $sesion = $estado['sesion_activa'] ?? null;

            if (!$sesion) {
                throw ValidationException::withMessages([
                    'caja' => 'No tienes una sesión de caja activa para generar el crédito de cambio.',
                ]);
            }

            $ventaOrigen = PosVenta::query()
                ->with([
                    'detalle.sku:psk_id,psk_nombre',
                    'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
                ])
                ->lockForUpdate()
                ->findOrFail((int) $datos['venta_origen_id']);

            $this->validarVentaOrigen($ventaOrigen, $sesion);

            $devoluciones = $this->resolverDevoluciones($ventaOrigen, $datos['devoluciones'] ?? []);
            if ($devoluciones->isEmpty()) {
                throw ValidationException::withMessages([
                    'devoluciones' => 'Selecciona al menos una partida para generar el crédito.',
                ]);
            }

            $sucursalId = (int) ($sesion['caja_scl_id'] ?? 0);
            $almacenEntradaId = $this->resolverAlmacenResguardo($sesion, $ventaOrigen);
            $creditoTotal = round((float) $devoluciones->sum('importe_credito'), 2);

            $credito = PosCreditoCambio::query()->create([
                'pcc_folio' => $this->crearFolio($sucursalId),
                'pcc_cse_id' => !empty($sesion['cse_id']) ? (int) $sesion['cse_id'] : null,
                'pcc_caj_id' => !empty($sesion['caja_id']) ? (int) $sesion['caja_id'] : null,
                'pcc_scl_id' => $sucursalId,
                'pcc_alm_id' => $almacenEntradaId,
                'pcc_usr_id' => (int) $usuario->usr_id,
                'pcc_cli_id' => $ventaOrigen->psv_cli_id ? (int) $ventaOrigen->psv_cli_id : null,
                'pcc_psv_origen_id' => (int) $ventaOrigen->psv_id,
                'pcc_estatus' => 'disponible',
                'pcc_total_credito' => $creditoTotal,
                'pcc_saldo_disponible' => $creditoTotal,
                'pcc_notas' => $datos['notas'] ?? null,
                'pcc_fecha_generado' => now(),
                'pcc_created_by_usr_id' => (int) $usuario->usr_id,
                'pcc_updated_by_usr_id' => (int) $usuario->usr_id,
            ]);

            foreach ($devoluciones as $devolucion) {
                PosCreditoCambioDetalle::query()->create([
                    'pcdv_pcc_id' => $credito->pcc_id,
                    'pcdv_psv_origen_id' => (int) $ventaOrigen->psv_id,
                    'pcdv_pvd_origen_id' => $devolucion['detalle']->pvd_id,
                    'pcdv_psk_id' => $devolucion['detalle']->pvd_psk_id,
                    'pcdv_alm_id' => $almacenEntradaId,
                    'pcdv_cantidad' => $devolucion['cantidad'],
                    'pcdv_precio_unitario' => $devolucion['precio_unitario'],
                    'pcdv_importe_credito' => $devolucion['importe_credito'],
                    'pcdv_condicion' => $devolucion['condicion'],
                    'pcdv_created_by_usr_id' => (int) $usuario->usr_id,
                    'pcdv_updated_by_usr_id' => (int) $usuario->usr_id,
                ]);

                $this->inventarioBaseService->registrarEntrada($request, [
                    'min_psk_id' => $devolucion['detalle']->pvd_psk_id,
                    'min_scl_id' => (int) $ventaOrigen->psv_scl_id,
                    'min_alm_id' => $almacenEntradaId,
                    'min_cantidad' => $devolucion['cantidad'],
                    'min_fecha_movimiento' => now()->toDateTimeString(),
                    'min_motivo_texto' => 'Entrada por crédito de cambio ' . $credito->pcc_folio,
                    'min_documento_referencia' => $credito->pcc_folio,
                    'min_precio_unitario' => $devolucion['precio_unitario'],
                    'min_subtotal_linea' => round($devolucion['cantidad'] * $devolucion['precio_unitario'], 2),
                    'min_descuento_linea' => 0,
                    'min_total_linea' => $devolucion['importe_credito'],
                    'min_observaciones' => 'Crédito global de cambio.',
                ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.credito_cambio.generar',
                'tbl_pos_creditos_cambio_pcc',
                (string) $credito->pcc_id,
                [
                    'pcc_folio' => $credito->pcc_folio,
                    'psv_origen_id' => $ventaOrigen->psv_id,
                    'psv_folio_origen' => $ventaOrigen->psv_folio,
                    'credito_total' => $creditoTotal,
                    'devoluciones' => $devoluciones->count(),
                    'almacen_resguardo_id' => $almacenEntradaId,
                ]
            );

            return $credito->fresh([
                'detalle.sku:psk_id,psk_nombre,psk_codigo',
                'ventaOrigen:psv_id,psv_folio',
                'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
                'almacen:alm_id,alm_nombre',
            ]);
        });
    }

    public function buscarDisponiblePorFolio(string $folio, int $sucursalId, float $totalVenta = 0): ?array
    {
        $folio = $this->normalizarFolio($folio);
        if ($folio === '') {
            return null;
        }

        $credito = PosCreditoCambio::query()
            ->with([
                'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
                'ventaOrigen:psv_id,psv_folio',
                'almacen:alm_id,alm_nombre',
            ])
            ->where('pcc_folio', $folio)
            ->first();

        if (!$credito) {
            return null;
        }

        return $this->mapCredito($credito, $sucursalId, $totalVenta);
    }

    public function aplicarEnVenta(Request $request, Usuario $usuario, PosVenta $venta, ?string $folio, ?float $montoEsperado = null): float
    {
        $folio = trim((string) $folio);
        if ($folio === '') {
            return 0;
        }

        $credito = PosCreditoCambio::query()
            ->where('pcc_folio', $folio)
            ->lockForUpdate()
            ->first();

        if (!$credito) {
            throw ValidationException::withMessages([
                'credito_cambio_folio' => 'No se encontró el crédito de cambio indicado.',
            ]);
        }

        if ((int) $credito->pcc_scl_id !== (int) $venta->psv_scl_id) {
            throw ValidationException::withMessages([
                'credito_cambio_folio' => 'El crédito de cambio pertenece a otra sucursal.',
            ]);
        }

        if ((string) $credito->pcc_estatus === 'cancelado' || $credito->pcc_cancelado_at !== null) {
            throw ValidationException::withMessages([
                'credito_cambio_folio' => 'El crédito de cambio ya fue cancelado y no puede utilizarse.',
            ]);
        }

        $saldoDisponible = round((float) $credito->pcc_saldo_disponible, 2);
        if ($saldoDisponible <= 0) {
            throw ValidationException::withMessages([
                'credito_cambio_folio' => 'El crédito de cambio ya no tiene saldo disponible.',
            ]);
        }

        $baseAplicacion = $montoEsperado !== null
            ? max(0, (float) $montoEsperado)
            : (float) $venta->psv_total;
        $montoAplicado = round(min($saldoDisponible, $baseAplicacion), 2);
        if ($montoAplicado <= 0) {
            return 0;
        }

        PosCreditoCambioAplicacion::query()->create([
            'pca_pcc_id' => (int) $credito->pcc_id,
            'pca_psv_id' => (int) $venta->psv_id,
            'pca_cse_id' => $venta->psv_cse_id ? (int) $venta->psv_cse_id : null,
            'pca_caj_id' => $venta->psv_caj_id ? (int) $venta->psv_caj_id : null,
            'pca_scl_id' => (int) $venta->psv_scl_id,
            'pca_usr_id' => (int) $usuario->usr_id,
            'pca_monto_aplicado' => $montoAplicado,
            'pca_fecha_aplicacion' => now(),
            'pca_created_by_usr_id' => (int) $usuario->usr_id,
            'pca_updated_by_usr_id' => (int) $usuario->usr_id,
        ]);

        $nuevoSaldo = round($saldoDisponible - $montoAplicado, 2);
        $credito->update([
            'pcc_saldo_disponible' => $nuevoSaldo,
            'pcc_estatus' => $nuevoSaldo > 0 ? 'parcial' : 'aplicado',
            'pcc_updated_by_usr_id' => (int) $usuario->usr_id,
        ]);

        $this->auditoriaService->registrarAccion(
            $request,
            'pos.credito_cambio.aplicar',
            'tbl_pos_creditos_cambio_pcc',
            (string) $credito->pcc_id,
            [
                'pcc_folio' => $credito->pcc_folio,
                'psv_id' => $venta->psv_id,
                'psv_folio' => $venta->psv_folio,
                'monto_aplicado' => $montoAplicado,
                'saldo_restante' => $nuevoSaldo,
            ]
        );

        return $montoAplicado;
    }

    public function revertirAplicacionesVenta(Request $request, Usuario $usuario, PosVenta $venta): void
    {
        $aplicaciones = PosCreditoCambioAplicacion::query()
            ->with('credito')
            ->where('pca_psv_id', (int) $venta->psv_id)
            ->lockForUpdate()
            ->get();

        foreach ($aplicaciones as $aplicacion) {
            $credito = PosCreditoCambio::query()
                ->lockForUpdate()
                ->find($aplicacion->pca_pcc_id);

            if (!$credito) {
                continue;
            }

            $nuevoSaldo = round((float) $credito->pcc_saldo_disponible + (float) $aplicacion->pca_monto_aplicado, 2);
            $credito->update([
                'pcc_saldo_disponible' => $nuevoSaldo,
                'pcc_estatus' => $nuevoSaldo > 0 ? 'disponible' : $credito->pcc_estatus,
                'pcc_updated_by_usr_id' => (int) $usuario->usr_id,
            ]);

            $aplicacion->marcarComoEliminado();
            $aplicacion->forceFill([
                'pca_updated_by_usr_id' => (int) $usuario->usr_id,
            ])->save();

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.credito_cambio.revertir_aplicacion',
                'tbl_pos_creditos_cambio_pcc',
                (string) $credito->pcc_id,
                [
                    'pcc_folio' => $credito->pcc_folio,
                    'psv_id' => $venta->psv_id,
                    'psv_folio' => $venta->psv_folio,
                    'monto_revertido' => (float) $aplicacion->pca_monto_aplicado,
                    'saldo_restaurado' => $nuevoSaldo,
                ]
            );
        }
    }

    public function listarDisponiblesParaSucursal(int $sucursalId, array $filtros = []): array
    {
        $query = PosCreditoCambio::query()
            ->with([
                'cliente:cli_id,cli_nombre,cli_apellido_paterno,cli_apellido_materno,cli_razon_social',
                'ventaOrigen:psv_id,psv_folio',
                'almacen:alm_id,alm_nombre',
            ])
            ->where('pcc_scl_id', $sucursalId);

        $folio = $this->normalizarFolio((string) ($filtros['folio'] ?? ''));
        if ($folio !== '') {
            $query->where('pcc_folio', 'like', '%' . $folio . '%');
        }

        $estatus = trim((string) ($filtros['estatus'] ?? ''));
        if ($estatus !== '') {
            $query->where('pcc_estatus', $estatus);
        }

        $cliente = mb_strtolower(trim((string) ($filtros['cliente'] ?? '')));
        if ($cliente !== '') {
            $query->whereHas('cliente', function ($sub) use ($cliente): void {
                $sub->whereRaw('LOWER(COALESCE(cli_razon_social, "")) like ?', ['%' . $cliente . '%'])
                    ->orWhereRaw('LOWER(COALESCE(cli_nombre, "")) like ?', ['%' . $cliente . '%'])
                    ->orWhereRaw('LOWER(COALESCE(cli_apellido_paterno, "")) like ?', ['%' . $cliente . '%'])
                    ->orWhereRaw('LOWER(COALESCE(cli_apellido_materno, "")) like ?', ['%' . $cliente . '%']);
            });
        }

        $creditos = $query
            ->orderByDesc('pcc_fecha_generado')
            ->orderByDesc('pcc_id')
            ->limit(150)
            ->get();

        return $creditos->map(fn (PosCreditoCambio $credito) => $this->mapCreditoListado($credito))->all();
    }

    public function crearFolio(int $sucursalId): string
    {
        $prefix = 'CDC-' . str_pad((string) $sucursalId, 3, '0', STR_PAD_LEFT) . '-';
        $last = PosCreditoCambio::query()
            ->withDeleted()
            ->where('pcc_folio', 'like', $prefix . '%')
            ->orderByDesc('pcc_id')
            ->value('pcc_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $next = ((int) substr($last, strlen($prefix))) + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function mapCredito(PosCreditoCambio $credito, int $sucursalId, float $totalVenta = 0): array
    {
        $clienteNombre = trim((string) ($credito->cliente?->cli_razon_social ?: implode(' ', array_filter([
            $credito->cliente?->cli_nombre,
            $credito->cliente?->cli_apellido_paterno,
            $credito->cliente?->cli_apellido_materno,
        ]))));
        $saldo = round((float) $credito->pcc_saldo_disponible, 2);

        return [
            'pcc_id' => (int) $credito->pcc_id,
            'pcc_folio' => (string) $credito->pcc_folio,
            'pcc_estatus' => (string) $credito->pcc_estatus,
            'pcc_total_credito' => (float) $credito->pcc_total_credito,
            'pcc_saldo_disponible' => $saldo,
            'pcc_sucursal_valida' => (int) $credito->pcc_scl_id === $sucursalId,
            'pcc_monto_aplicable' => round(min($saldo, max(0, $totalVenta)), 2),
            'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : 'Público general',
            'venta_origen_folio' => (string) ($credito->ventaOrigen?->psv_folio ?? ''),
            'almacen' => (string) ($credito->almacen?->alm_nombre ?? ''),
            'fecha_generado' => optional($credito->pcc_fecha_generado)->format('Y-m-d H:i:s'),
        ];
    }

    public function cantidadDevueltaActivaPorDetalle(int $detalleVentaId): float
    {
        $cantidadCambios = round((float) PosCambioDetalle::query()
            ->join('tbl_pos_ventas_psv as psv', 'psv.psv_id', '=', 'tbl_pos_cambios_detalle_pcd.pcd_psv_id')
            ->where('tbl_pos_cambios_detalle_pcd.pcd_pvd_origen_id', $detalleVentaId)
            ->where('tbl_pos_cambios_detalle_pcd.pcd_deleted', false)
            ->whereNull('tbl_pos_cambios_detalle_pcd.pcd_deleted_at')
            ->where('psv.psv_estatus', '!=', 'cancelada')
            ->sum('tbl_pos_cambios_detalle_pcd.pcd_cantidad'), 2);

        $cantidadCreditos = round((float) PosCreditoCambioDetalle::query()
            ->join('tbl_pos_creditos_cambio_pcc as pcc', 'pcc.pcc_id', '=', 'tbl_pos_creditos_cambio_detalle_pcdv.pcdv_pcc_id')
            ->where('tbl_pos_creditos_cambio_detalle_pcdv.pcdv_pvd_origen_id', $detalleVentaId)
            ->where('tbl_pos_creditos_cambio_detalle_pcdv.pcdv_deleted', false)
            ->whereNull('tbl_pos_creditos_cambio_detalle_pcdv.pcdv_deleted_at')
            ->where('pcc.pcc_estatus', '!=', 'cancelado')
            ->sum('tbl_pos_creditos_cambio_detalle_pcdv.pcdv_cantidad'), 2);

        return round($cantidadCambios + $cantidadCreditos, 2);
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
                'venta_origen_id' => 'Solo se pueden generar créditos a partir de una venta normal.',
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

            $devuelto = $this->cantidadDevueltaActivaPorDetalle($detalle->pvd_id);
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
                'condicion' => 'reventa',
            ];
        })->values();
    }

    private function resolverAlmacenResguardo(array $sesion, PosVenta $ventaOrigen): int
    {
        $almacenCajaId = (int) ($sesion['caja_alm_id'] ?? 0);
        if ($almacenCajaId > 0) {
            $valido = Almacen::query()
                ->where('alm_id', $almacenCajaId)
                ->where('alm_scl_id', (int) $ventaOrigen->psv_scl_id)
                ->where('alm_estatus', 'activo')
                ->exists();

            if ($valido) {
                return $almacenCajaId;
            }
        }

        return (int) $ventaOrigen->psv_alm_id;
    }

    private function normalizarFolio(string $folio): string
    {
        return mb_strtoupper(trim((string) preg_replace("/['’`´]+/u", '-', str_replace(' ', '', $folio))));
    }

    private function mapCreditoListado(PosCreditoCambio $credito): array
    {
        $clienteNombre = trim((string) ($credito->cliente?->cli_razon_social ?: implode(' ', array_filter([
            $credito->cliente?->cli_nombre,
            $credito->cliente?->cli_apellido_paterno,
            $credito->cliente?->cli_apellido_materno,
        ]))));

        return [
            'pcc_id' => (int) $credito->pcc_id,
            'pcc_folio' => (string) $credito->pcc_folio,
            'pcc_estatus' => (string) $credito->pcc_estatus,
            'pcc_total_credito' => (float) $credito->pcc_total_credito,
            'pcc_saldo_disponible' => (float) $credito->pcc_saldo_disponible,
            'pcc_fecha_generado' => optional($credito->pcc_fecha_generado)->format('Y-m-d H:i:s'),
            'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : 'Público general',
            'venta_origen_folio' => (string) ($credito->ventaOrigen?->psv_folio ?? ''),
            'almacen' => (string) ($credito->almacen?->alm_nombre ?? ''),
        ];
    }
}
