<?php

namespace App\Services\Operacion;

use App\Models\MovimientoInventario;
use App\Models\PosVenta;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosVentaCancelacionService
{
    public function __construct(
        private readonly InventarioBaseService $inventarioBaseService,
        private readonly AuditoriaService $auditoriaService,
        private readonly PosCreditoCambioService $posCreditoCambioService,
    ) {
    }

    public function cancelar(Request $request, Usuario $usuario, PosVenta $venta, string $motivo): PosVenta
    {
        return DB::transaction(function () use ($request, $usuario, $venta, $motivo): PosVenta {
            $venta = PosVenta::query()
                ->with([
                    'detalle',
                    'cambioDevoluciones',
                    'creditosCambioGenerados' => fn ($q) => $q
                        ->where('pcc_estatus', '!=', 'cancelado'),
                    'cambiosRelacionados' => fn ($q) => $q
                        ->where('psv_estatus', '!=', 'cancelada'),
                ])
                ->lockForUpdate()
                ->findOrFail($venta->psv_id);

            $this->validarCancelable($venta);

            $movimientos = $this->movimientosActivosVenta($venta->psv_folio);
            foreach ($movimientos as $movimiento) {
                $this->inventarioBaseService->cancelarMovimiento(
                    $request,
                    (int) $movimiento->min_id,
                    trim($motivo) !== '' ? trim($motivo) : 'Cancelación de venta POS.'
                );
            }

            $this->posCreditoCambioService->revertirAplicacionesVenta($request, $usuario, $venta);

            $venta->update([
                'psv_estatus' => 'cancelada',
                'psv_cancelado_at' => now(),
                'psv_cancelado_by_usr_id' => (int) $usuario->usr_id,
                'psv_cancelacion_motivo' => trim($motivo) !== '' ? trim($motivo) : 'Cancelación de venta POS.',
                'psv_updated_by_usr_id' => (int) $usuario->usr_id,
            ]);

            if (!empty($venta->psv_pdp_id) && $venta->psv_tipo_operacion === 'venta') {
                DB::table('tbl_pedidos_piso_pdp')
                    ->where('pdp_id', (int) $venta->psv_pdp_id)
                    ->update([
                        'pdp_estatus' => 'pendiente_cobro',
                        'pdp_updated_by_usr_id' => (int) $usuario->usr_id,
                    ]);
            }

            $this->auditoriaService->registrarAccion(
                $request,
                'pos.venta.cancelar',
                'tbl_pos_ventas_psv',
                (string) $venta->psv_id,
                [
                    'psv_folio' => $venta->psv_folio,
                    'psv_tipo_operacion' => $venta->psv_tipo_operacion,
                    'psv_venta_origen_id' => $venta->psv_venta_origen_id,
                    'motivo' => $venta->psv_cancelacion_motivo,
                    'movimientos_revertidos' => $movimientos->count(),
                ]
            );

            return $venta->fresh([
                'detalle',
                'ventaOrigen',
                'cambioDevoluciones',
                'canceladoPor:usr_id,usr_nombre,usr_usuario',
            ]);
        });
    }

    private function validarCancelable(PosVenta $venta): void
    {
        if ($venta->psv_estatus === 'cancelada' || $venta->psv_cancelado_at !== null) {
            throw ValidationException::withMessages([
                'venta' => 'La venta seleccionada ya fue cancelada.',
            ]);
        }

        if ($venta->cambiosRelacionados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'venta' => 'La venta ya tiene cambios/devoluciones asociados y no puede cancelarse.',
            ]);
        }

        if ($venta->creditosCambioGenerados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'venta' => 'La venta ya tiene vales de cambio asociados y no puede cancelarse.',
            ]);
        }
    }

    private function movimientosActivosVenta(string $folio): Collection
    {
        return MovimientoInventario::query()
            ->where('min_documento_referencia', $folio)
            ->where('min_estatus', 'activo')
            ->where('min_es_reversa', false)
            ->orderBy('min_id')
            ->lockForUpdate()
            ->get();
    }
}
