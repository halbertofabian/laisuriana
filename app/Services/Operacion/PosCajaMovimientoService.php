<?php

namespace App\Services\Operacion;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\PosVenta;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCajaMovimientoService
{
    public function __construct(
        private readonly PosCajaSesionService $posCajaSesionService,
        private readonly AuditoriaService $auditoriaService,
    ) {
    }

    public function registrar(Request $request, Usuario $usuario, array $datos): CajaMovimiento
    {
        return DB::transaction(function () use ($request, $usuario, $datos): CajaMovimiento {
            $tipo = (string) $datos['tipo'];
            $sesion = $this->sesionActivaUsuario($usuario);
            $monto = round((float) $datos['monto'], 2);
            $resumen = $this->resumenPorSesion((int) $sesion['cse_id']);
            $efectivoDisponible = (float) ($resumen['efectivo_disponible'] ?? 0);
            $usuarioAutorizaId = $tipo === 'retiro'
                ? (int) ($datos['autoriza_usr_id'] ?? 0)
                : (int) $usuario->usr_id;
            $denominaciones = $tipo === 'retiro'
                ? $this->normalizarDenominaciones((array) ($datos['denominaciones'] ?? []))
                : null;

            if ($monto > $efectivoDisponible) {
                throw ValidationException::withMessages([
                    'monto' => 'No hay efectivo suficiente en caja para registrar este movimiento.',
                ]);
            }

            $movimiento = CajaMovimiento::query()->create([
                'cjm_folio' => $this->crearFolio((int) $sesion['caja_id'], $tipo),
                'cjm_cse_id' => (int) $sesion['cse_id'],
                'cjm_caj_id' => (int) $sesion['caja_id'],
                'cjm_scl_id' => (int) ($sesion['caja_scl_id'] ?? 0),
                'cjm_usr_cajero_id' => (int) $usuario->usr_id,
                'cjm_usr_autorizo_id' => $usuarioAutorizaId,
                'cjm_tipo' => $tipo,
                'cjm_monto' => $monto,
                'cjm_denominaciones' => $denominaciones,
                'cjm_categoria' => $tipo === 'gasto' ? trim((string) ($datos['categoria'] ?? '')) : null,
                'cjm_referencia' => trim((string) ($datos['referencia'] ?? '')) ?: null,
                'cjm_motivo' => trim((string) ($datos['motivo'] ?? '')) ?: null,
                'cjm_estatus' => 'registrado',
                'cjm_fecha_movimiento' => now(),
                'cjm_created_by_usr_id' => (int) $usuario->usr_id,
                'cjm_updated_by_usr_id' => (int) $usuario->usr_id,
            ]);

            $this->auditoriaService->registrarAccion(
                $request,
                $tipo === 'retiro' ? 'pos.caja.retiro' : 'pos.caja.gasto',
                'tbl_caja_movimientos_cjm',
                (string) $movimiento->cjm_id,
                [
                    'cjm_folio' => $movimiento->cjm_folio,
                    'cjm_tipo' => $movimiento->cjm_tipo,
                    'cjm_monto' => $movimiento->cjm_monto,
                    'cjm_denominaciones' => $movimiento->cjm_denominaciones,
                    'cjm_cse_id' => $movimiento->cjm_cse_id,
                    'cjm_categoria' => $movimiento->cjm_categoria,
                ]
            );

            return $movimiento->fresh([
                'caja:caj_id,caj_nombre',
                'cajero:usr_id,usr_nombre,usr_usuario',
                'autorizadoPor:usr_id,usr_nombre,usr_usuario',
                'cajaSesion:cse_id,cse_caj_id,cse_monto_apertura',
                'cajaSesion.caja:caj_id,caj_nombre',
            ]);
        });
    }

    private function normalizarDenominaciones(array $capturadas): array
    {
        $valores = [
            '1000' => 1000,
            '500' => 500,
            '200' => 200,
            '100' => 100,
            '50' => 50,
            '20' => 20,
            '10' => 10,
            '5' => 5,
            '2' => 2,
            '1' => 1,
            '0_50' => 0.5,
        ];

        return collect($valores)
            ->mapWithKeys(fn (float|int $valor, string $clave): array => [
                $clave => [
                    'etiqueta' => '$' . number_format((float) $valor, $valor < 1 ? 2 : 0),
                    'tipo' => $valor >= 20 ? 'billete' : 'moneda',
                    'cantidad' => max(0, (int) ($capturadas[$clave] ?? 0)),
                    'valor' => (float) $valor,
                ],
            ])
            ->all();
    }

    public function resumenSesionActual(Usuario $usuario): ?array
    {
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sesion = $estado['sesion_activa'] ?? null;

        if (!$sesion) {
            return null;
        }

        return $this->resumenPorSesion((int) $sesion['cse_id']);
    }

    public function resumenPorSesion(int $sesionId): array
    {
        $sesion = CajaSesion::query()
            ->with('caja:caj_id,caj_nombre,caj_retiro_umbral')
            ->findOrFail($sesionId);

        $ventas = PosVenta::query()
            ->where('psv_cse_id', $sesionId)
            ->where('psv_deleted', false)
            ->whereNull('psv_deleted_at')
            ->where('psv_estatus', '!=', 'cancelada')
            ->get([
                'psv_id',
                'psv_total',
                'psv_pagado',
                'psv_cambio',
                'psv_metodo_pago',
                'psv_pago_detalle',
                'psv_tipo_operacion',
                'psv_credito_cambio',
            ]);

        $movimientos = CajaMovimiento::query()
            ->where('cjm_cse_id', $sesionId)
            ->where('cjm_deleted', false)
            ->whereNull('cjm_deleted_at')
            ->where('cjm_estatus', 'registrado')
            ->get([
                'cjm_id',
                'cjm_tipo',
                'cjm_monto',
            ]);

        $efectivoVentasNeto = 0.0;
        $creditoCambios = 0.0;
        $cantidadCambios = 0;
        $importeCobradoCambios = 0.0;
        $totalVendido = 0.0;
        $ventasPorMetodo = [
            'efectivo' => 0.0,
            'tarjeta' => 0.0,
            'mixto' => 0.0,
            'monedero_electronico' => 0.0,
            'sin_pago' => 0.0,
        ];

        foreach ($ventas as $venta) {
            $detallePago = is_array($venta->psv_pago_detalle) ? $venta->psv_pago_detalle : [];
            $efectivoRecibido = (float) ($detallePago['efectivo'] ?? 0);
            $metodo = (string) ($venta->psv_metodo_pago ?? '');
            $creditoCambio = round((float) ($venta->psv_credito_cambio ?? 0), 2);
            // Los cambios creados antes de este ajuste se guardaron como sin_pago.
            if ($metodo === 'sin_pago' && $creditoCambio > 0) {
                $metodo = 'monedero_electronico';
            }
            $montoVenta = round((float) ($venta->psv_total ?? 0), 2);

            $totalVendido += $montoVenta;
            if ($creditoCambio > 0) {
                $ventasPorMetodo['monedero_electronico'] += $creditoCambio;
            }
            if ($metodo !== 'monedero_electronico' && array_key_exists($metodo, $ventasPorMetodo)) {
                $ventasPorMetodo[$metodo] += $montoVenta;
            }

            if ($efectivoRecibido <= 0 && (string) $venta->psv_metodo_pago === 'efectivo') {
                $efectivoRecibido = (float) $venta->psv_pagado;
            }

            $efectivoVentasNeto += round(max(0, $efectivoRecibido - (float) $venta->psv_cambio), 2);

            if ((string) ($venta->psv_tipo_operacion ?? 'venta') === 'cambio') {
                $cantidadCambios++;
                $creditoCambios += $creditoCambio;
                $importeCobradoCambios += round((float) ($venta->psv_total ?? 0), 2);
            }
        }

        $totalRetiros = round((float) $movimientos->where('cjm_tipo', 'retiro')->sum('cjm_monto'), 2);
        $totalGastos = round((float) $movimientos->where('cjm_tipo', 'gasto')->sum('cjm_monto'), 2);
        $inicioCaja = round((float) ($sesion->cse_monto_apertura ?? 0), 2);
        $umbralRetiro = round((float) ($sesion->caja?->caj_retiro_umbral ?? 0), 2);
        $efectivoDisponible = round($inicioCaja + $efectivoVentasNeto - $totalRetiros - $totalGastos, 2);

        return [
            'inicio_caja' => $inicioCaja,
            'efectivo_ventas_neto' => round($efectivoVentasNeto, 2),
            'efectivo_disponible' => $efectivoDisponible,
            'umbral_retiro' => $umbralRetiro,
            'retiro_recomendado' => $umbralRetiro > 0 && $efectivoDisponible >= $umbralRetiro,
            'excedente_umbral' => $umbralRetiro > 0 ? round(max(0, $efectivoDisponible - $umbralRetiro), 2) : 0,
            'ventas_del_dia' => $ventas->count(),
            'total_vendido' => round($totalVendido, 2),
            'ventas_por_metodo' => [
                ['clave' => 'efectivo', 'label' => 'Efectivo', 'monto' => round($ventasPorMetodo['efectivo'], 2)],
                ['clave' => 'tarjeta', 'label' => 'Tarjeta', 'monto' => round($ventasPorMetodo['tarjeta'], 2)],
                ['clave' => 'mixto', 'label' => 'Mixto', 'monto' => round($ventasPorMetodo['mixto'], 2)],
                ['clave' => 'monedero_electronico', 'label' => 'Monedero electrónico', 'monto' => round($ventasPorMetodo['monedero_electronico'], 2)],
                ['clave' => 'sin_pago', 'label' => 'Sin pago', 'monto' => round($ventasPorMetodo['sin_pago'], 2)],
            ],
            'credito_cambios' => round($creditoCambios, 2),
            'cantidad_cambios' => $cantidadCambios,
            'importe_cobrado_cambios' => round($importeCobradoCambios, 2),
            'gastos' => $totalGastos,
            'retiros' => $totalRetiros,
        ];
    }

    public function crearFolio(int $cajaId, string $tipo): string
    {
        $prefix = ($tipo === 'retiro' ? 'RET' : 'GAS') . '-' . str_pad((string) $cajaId, 3, '0', STR_PAD_LEFT) . '-';
        $last = CajaMovimiento::query()
            ->where('cjm_folio', 'like', $prefix . '%')
            ->orderByDesc('cjm_id')
            ->value('cjm_folio');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $num = (int) substr($last, strlen($prefix));
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function sesionActivaUsuario(Usuario $usuario): array
    {
        $estado = $this->posCajaSesionService->estadoUsuario($usuario);
        $sesion = $estado['sesion_activa'] ?? null;

        if (!$sesion) {
            throw ValidationException::withMessages([
                'caja' => 'No tienes una sesión de caja activa para registrar movimientos.',
            ]);
        }

        return $sesion;
    }
}
