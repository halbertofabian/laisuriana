<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ComisionV2Departamento;
use App\Models\ComisionV2Periodo;
use App\Models\Linea;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionHistoricoService;
use App\Services\Reportes\ComisionV2Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Escenario didáctico local. No se ejecuta desde DatabaseSeeder. */
class ComisionesSeptiembre2026DemoSeeder extends Seeder
{
    public const MARCA = 'DEMO-COM-SEP2026';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Esta simulación solo se permite en local o testing.');
        }

        $periodo = DB::transaction(function () {
            $almacenes = Almacen::query()->whereIn('alm_nombre', ['La I. Suriana', 'I. Suriana'])
                ->where('alm_estatus', 'activo')->where('alm_deleted', false)->orderBy('alm_nombre')->get();
            if ($almacenes->count() !== 2 || $almacenes->pluck('alm_scl_id')->unique()->count() !== 1) {
                throw new RuntimeException('Se requieren los dos almacenes existentes de Suriana en la misma sucursal.');
            }
            $sucursalId = (int) $almacenes->first()->alm_scl_id;
            DB::table('tbl_sucursales_scl')->where('scl_id', $sucursalId)->lockForUpdate()->firstOrFail();
            $existente = ComisionV2Periodo::query()->where('cmp_scl_id', $sucursalId)->whereDate('cmp_periodo', '2026-09-01')->first();
            if ($existente) {
                if ($existente->cmp_ultimo_motivo_cambio !== self::MARCA) {
                    throw new RuntimeException('Septiembre ya tiene configuración. No se reemplazó.');
                }

                // Repetir la siembra conserva incluso los ejercicios editados por el usuario.
                return $existente;
            }
            if (PosVenta::query()->where('psv_scl_id', $sucursalId)->whereBetween('psv_fecha_cobro', ['2026-09-01', '2026-09-30 23:59:59'])->exists()) {
                throw new RuntimeException('Septiembre ya tiene operaciones. No se mezclaron con la simulación.');
            }
            if (DB::table('tbl_comision_historicos_chv')->where('chv_scl_id', $sucursalId)->whereDate('chv_periodo', '2025-09-01')->exists()) {
                throw new RuntimeException('Ya existe histórico de septiembre de 2025. No se reemplazó.');
            }
            $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
            $departamentos = [];
            $skus = [];
            foreach (['ROPA' => 'LNA_CABALLERO', 'TELAS' => 'LNA_TELAS'] as $clave => $lineaClave) {
                $dep = ComisionV2Departamento::query()->where('cmd_clave', $clave)->where('cmd_estatus', 'activo')->firstOrFail();
                $linea = Linea::query()->where('lna_clave', $lineaClave)->where('lna_estatus', 'activo')->firstOrFail();
                $skus[$clave] = ProductoSku::query()->where('psk_estatus', 'activo')->where('psk_deleted', false)
                    ->whereHas('producto', fn ($q) => $q->where('prd_lna_id', $linea->lna_id)->where('prd_estatus', 'activo')->where('prd_deleted', false))->firstOrFail();
                $departamentos[$clave] = ['id' => $dep->cmd_id, 'linea' => $linea->lna_id];
                foreach ($almacenes as $almacen) {
                    app(ComisionHistoricoService::class)->guardar([
                        'periodo' => '2025-09', 'almacen_id' => $almacen->alm_id, 'linea_id' => $linea->lna_id,
                        'ventas_netas' => $clave === 'ROPA' ? 220000 : 70000,
                        'autoservicio' => $clave === 'ROPA' ? 20000 : 10000,
                        'referencia' => self::MARCA,
                        'observaciones' => 'Histórico ficticio para capacitación. Incluye autoservicio; no representa ventas reales de 2025.',
                    ], $sucursalId, $admin->usr_id);
                }
            }
            $cajas = [];
            foreach ($almacenes as $i => $almacen) {
                $caja = Caja::query()->create([
                    'caj_scl_id' => $sucursalId, 'caj_alm_id' => $almacen->alm_id,
                    'caj_clave' => 'DEMO-SEP26-'.($i + 1), 'caj_nombre' => 'DEMO septiembre 2026 '.($i + 1),
                    'caj_estatus' => 'inactivo', 'caj_updated_by_usr_id' => $admin->usr_id,
                ]);
                $sesion = CajaSesion::query()->create([
                    'cse_caj_id' => $caja->caj_id, 'cse_scl_id' => $sucursalId,
                    'cse_usr_apertura_id' => $admin->usr_id, 'cse_monto_apertura' => 0,
                    'cse_abierta_at' => '2026-09-01 09:00:00', 'cse_cerrada_at' => '2026-09-30 20:00:00', 'cse_estatus' => 'cerrada',
                ]);
                $cajas[] = [$almacen, $caja, $sesion];
            }
            // Neto esperado, tasa y motivo de cada caso. Cuatro participantes de Ropa y dos de Telas.
            $casos = [
                ['ana', 'Ana', 'ROPA', 88000, 0.9, 'Alcanza 80% de la meta'],
                ['bruno', 'Bruno', 'ROPA', 110000, 0.9, 'Alcanza exactamente 100%'],
                ['carla', 'Carla', 'ROPA', 132000, 0.9, 'Alcanza 120%; incluye descuento y devolución'],
                ['diego', 'Diego', 'ROPA', 109999.99, 0.9, 'Falta un centavo para la meta'],
                ['elena', 'Elena', 'TELAS', 79200, 1.0, 'DEMO: tasa especial del 1% para comparar'],
                ['fabio', 'Fabio', 'TELAS', 66000, 0.0, 'DEMO: tasa cero aun alcanzando la meta'],
            ];
            $vendedores = [];
            foreach ($casos as $i => [$login, $nombre, $dep, $neto, $tasa, $motivo]) {
                $usuario = Usuario::query()->create([
                    'usr_usuario' => 'demo.sep26.'.$login, 'usr_nombre' => 'DEMO '.$nombre,
                    'usr_password' => Hash::make(bin2hex(random_bytes(32))), 'usr_estatus' => 'activo',
                ]);
                UsuarioSucursal::query()->create([
                    'usc_usr_id' => $usuario->usr_id, 'usc_scl_id' => $sucursalId,
                    'usc_es_predeterminada' => true, 'usc_estatus' => 'activo',
                ]);
                $vendedores[$usuario->usr_id] = [
                    'habilitado' => true, 'departamento_id' => $departamentos[$dep]['id'],
                    'numero' => 'DEMO-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'meta' => 0, 'tasa' => $tasa, 'motivo' => $motivo,
                ];
                $primera = round($neto / 2, 2);
                $esCarla = $login === 'carla';
                $detalle = $this->venta($cajas[0], $admin, $skus[$dep], $usuario, $primera + ($esCarla ? 4000 : 0), $login.'-A', 5 + $i, $esCarla ? 1000 : 0);
                $this->venta($cajas[1], $admin, $skus[$dep], $usuario, round($neto - $primera, 2), $login.'-B', 18 + $i);
                if ($esCarla) {
                    // 71,000 brutos - 1,000 descuento + 66,000 otra venta - 4,000 devolución = 132,000.
                    [$almacen, $caja, $sesion] = $cajas[0];
                    $cambio = PosVenta::query()->create([
                        'psv_folio' => self::MARCA.'-carla-DEV', 'psv_scl_id' => $sucursalId,
                        'psv_alm_id' => $almacen->alm_id, 'psv_caj_id' => $caja->caj_id, 'psv_cse_id' => $sesion->cse_id,
                        'psv_usr_id' => $admin->usr_id, 'psv_tipo_operacion' => 'cambio', 'psv_venta_origen_id' => $detalle->pvd_psv_id,
                        'psv_estatus' => 'cobrada', 'psv_subtotal' => 0, 'psv_descuento' => 0, 'psv_credito_cambio' => 4000,
                        'psv_total' => 0, 'psv_pagado' => 0, 'psv_cambio' => 0,
                        'psv_fecha_cobro' => '2026-09-26 12:00:00', 'psv_notas' => self::MARCA.' Devolución ficticia.',
                    ]);
                    PosCambioDetalle::query()->create([
                        'pcd_psv_id' => $cambio->psv_id, 'pcd_pvd_origen_id' => $detalle->pvd_id,
                        'pcd_psv_origen_id' => $detalle->pvd_psv_id, 'pcd_psk_id' => $detalle->pvd_psk_id,
                        'pcd_alm_id' => $almacen->alm_id, 'pcd_cantidad' => 4, 'pcd_precio_unitario' => 1000,
                        'pcd_importe_credito' => 4000, 'pcd_condicion' => 'reventa',
                    ]);
                }
                if ($login === 'ana') {
                    $this->venta($cajas[0], $admin, $skus[$dep], $usuario, 50000, 'ana-CANCELADA', 27, 0, 'cancelada');
                }
            }
            $this->venta($cajas[0], $admin, $skus['ROPA'], null, 12000, 'AUTO-ROPA', 28);
            $this->venta($cajas[1], $admin, $skus['TELAS'], null, 8000, 'AUTO-TELAS', 29);
            $datos = ['periodo' => '2026-09', 'almacen_ids' => $almacenes->pluck('alm_id')->all(), 'motivo_cambio' => self::MARCA, 'departamentos' => [], 'vendedores' => $vendedores];
            foreach ($departamentos as $dep) {
                $datos['departamentos'][$dep['id']] = ['habilitado' => true, 'linea_ids' => [$dep['linea']], 'incremento_meta' => 10, 'meta_comun' => null];
            }
            $service = app(ComisionV2Service::class);
            $periodo = $service->guardar($datos, $sucursalId, $admin->usr_id);
            $periodo = $service->aprobar($periodo, $admin->usr_id);
            // Verificar con el motor real antes de confirmar la transacción.
            $estimaciones = $service->estimacionAdministrativa($periodo)->keyBy('nombre');
            $comisiones = [0, 326.70, 392.04, 0, 261.36, 0];
            foreach ($casos as $i => [$login, $nombre, $dep, $neto]) {
                $fila = $estimaciones->get('DEMO '.$nombre);
                if (! $fila || abs($fila->ventas - $neto) > 0.001 || abs($fila->meta - ($dep === 'ROPA' ? 110000 : 66000)) > 0.001 || abs($fila->comision - $comisiones[$i]) > 0.001) {
                    throw new RuntimeException('La verificación del escenario falló para '.$nombre.'. Se revirtió la siembra.');
                }
            }

            return $periodo;
        });
        $this->command?->info('Simulación septiembre 2026: '.$periodo->cmp_estatus.'. Configuración editable; comisiones estimadas.');
        $this->command?->table(['Vendedor', 'Neto', 'Meta', 'Avance %', 'Tasa %', 'Comisión'], app(ComisionV2Service::class)->estimacionAdministrativa($periodo)->map(fn ($r) => [$r->nombre, $r->ventas, $r->meta, $r->cumplimiento, $r->tasa, $r->comision])->all());
    }

    private function venta(array $contexto, Usuario $admin, ProductoSku $sku, ?Usuario $vendedor, float $neto, string $sufijo, int $dia, float $descuento = 0, string $estado = 'cobrada'): PosVentaDetalle
    {
        [$almacen, $caja, $sesion] = $contexto;
        $venta = PosVenta::query()->create([
            'psv_folio' => self::MARCA.'-'.$sufijo, 'psv_scl_id' => $almacen->alm_scl_id,
            'psv_alm_id' => $almacen->alm_id, 'psv_caj_id' => $caja->caj_id, 'psv_cse_id' => $sesion->cse_id,
            'psv_usr_id' => $admin->usr_id, 'psv_tipo_operacion' => 'venta', 'psv_estatus' => $estado,
            'psv_subtotal' => $neto, 'psv_descuento' => 0, 'psv_total' => $neto,
            'psv_pagado' => $estado === 'cobrada' ? $neto : 0, 'psv_cambio' => 0,
            'psv_fecha_cobro' => sprintf('2026-09-%02d 12:00:00', $dia),
            'psv_notas' => self::MARCA.' Venta ficticia para capacitación; sin movimientos de caja o inventario.',
        ]);

        return PosVentaDetalle::query()->create([
            'pvd_psv_id' => $venta->psv_id, 'pvd_psk_id' => $sku->psk_id, 'pvd_usr_id' => $vendedor?->usr_id,
            'pvd_cantidad' => $descuento > 0 ? 70 : 1, 'pvd_precio_unitario' => ($neto + $descuento) / ($descuento > 0 ? 70 : 1),
            'pvd_descuento_porcentaje' => $descuento > 0 ? round($descuento / ($neto + $descuento) * 100, 2) : 0,
            'pvd_descuento_importe' => $descuento, 'pvd_importe' => $neto,
        ]);
    }
}
