<?php

namespace App\Console\Commands;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ComisionV2Departamento;
use App\Models\ComisionV2Periodo;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionV2Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PrepararDemoComisionesV2Command extends Command
{
    protected $signature = 'demo:comisiones-v2 {--limpiar : Elimina únicamente el escenario V2 generado por este comando}';

    protected $description = 'Prepara o elimina un escenario demostrativo, aislado y repetible de metas y comisiones V2';

    private const MARCA = 'DEMO COMISIONES V2 - DATOS SINTETICOS';

    private const FOLIO_PREFIJO = 'DEMO-V2-';

    private const CLAVE_CAJA_PREFIJO = 'DEMO-V2-CAJA-';

    private const PASSWORD = 'Demo2026!';

    private const USUARIOS = [
        'comision.ana' => ['nombre' => 'Ana Demo', 'numero' => 'V2-01', 'departamento' => 'ROPA', 'tasa' => 0.9, 'motivo' => null],
        'comision.bruno' => ['nombre' => 'Bruno Demo', 'numero' => 'V2-02', 'departamento' => 'ROPA', 'tasa' => 0.0, 'motivo' => 'Caso demostrativo de tasa individual en 0%.'],
        'comision.carla' => ['nombre' => 'Carla Demo', 'numero' => 'V2-03', 'departamento' => 'TELAS', 'tasa' => 0.9, 'motivo' => null],
        'comision.diego' => ['nombre' => 'Diego Demo', 'numero' => 'V2-04', 'departamento' => 'TELAS', 'tasa' => 1.0, 'motivo' => 'Caso demostrativo de tasa individual máxima en 1%.'],
    ];

    public function handle(ComisionV2Service $comisiones): int
    {
        if ($this->option('limpiar')) {
            return $this->limpiar();
        }

        try {
            $estimaciones = DB::transaction(function () use ($comisiones) {
                $admin = Usuario::query()->where('usr_usuario', 'admin')->first()
                    ?? throw new RuntimeException('No se encontró el usuario admin.');
                $almacenes = Almacen::query()
                    ->whereIn('alm_nombre', ['I. Suriana', 'La I. Suriana'])
                    ->where('alm_estatus', 'activo')->where('alm_deleted', false)
                    ->get()->keyBy('alm_nombre');
                if ($almacenes->count() !== 2 || $almacenes->pluck('alm_scl_id')->unique()->count() !== 1) {
                    throw new RuntimeException('Se requieren los almacenes activos I. Suriana y La I. Suriana en una misma sucursal.');
                }
                $sucursalId = (int) $almacenes->first()->alm_scl_id;
                $periodoTexto = now()->format('Y-m');
                $periodoExistente = ComisionV2Periodo::query()
                    ->where('cmp_scl_id', $sucursalId)
                    ->whereDate('cmp_periodo', now()->startOfMonth()->toDateString())
                    ->first();
                if ($periodoExistente && ! str_starts_with((string) $periodoExistente->cmp_ultimo_motivo_cambio, self::MARCA)) {
                    throw new RuntimeException('Ya existe una configuración V2 real para el mes actual. No se modificó.');
                }

                $rolVendedorId = Rol::query()->where('rol_nombre', 'Vendedor piso')->value('rol_id')
                    ?? throw new RuntimeException('No se encontró el rol Vendedor piso.');
                $usuarios = collect(self::USUARIOS)->mapWithKeys(function (array $datos, string $usuario) use ($admin, $sucursalId, $rolVendedorId): array {
                    $registro = Usuario::query()->updateOrCreate(
                        ['usr_usuario' => $usuario],
                        [
                            'usr_nombre' => $datos['nombre'],
                            'usr_password' => Hash::make(self::PASSWORD),
                            'usr_estatus' => 'activo',
                            'usr_updated_by_usr_id' => $admin->usr_id,
                        ],
                    );
                    $registro->forceFill(['usr_deleted' => false, 'usr_deleted_at' => null])->save();
                    UsuarioSucursal::query()->updateOrCreate(
                        ['usc_usr_id' => $registro->usr_id, 'usc_scl_id' => $sucursalId],
                        ['usc_es_predeterminada' => true, 'usc_estatus' => 'activo', 'usc_deleted' => false, 'usc_deleted_at' => null],
                    );
                    UsuarioRol::query()->updateOrCreate(
                        ['url_usr_id' => $registro->usr_id, 'url_rol_id' => $rolVendedorId],
                        ['url_estatus' => 'activo', 'url_deleted' => false, 'url_deleted_at' => null],
                    );

                    return [$usuario => ['registro' => $registro, ...$datos]];
                });

                $departamentos = ComisionV2Departamento::query()
                    ->with('lineas:lna_id,lna_nombre')
                    ->whereIn('cmd_clave', ['ROPA', 'TELAS'])
                    ->where('cmd_estatus', 'activo')
                    ->get()->keyBy('cmd_clave');
                if ($departamentos->count() !== 2 || $departamentos->contains(fn ($departamento) => $departamento->lineas->isEmpty())) {
                    throw new RuntimeException('Los departamentos Ropa y Telas deben estar activos y tener líneas asignadas.');
                }

                $configDepartamentos = [];
                foreach ($departamentos as $departamento) {
                    $configDepartamentos[$departamento->cmd_id] = [
                        'habilitado' => true,
                        'linea_ids' => $departamento->lineas->pluck('lna_id')->map(fn ($id) => (int) $id)->all(),
                        'incremento_meta' => 0,
                        'meta_comun' => 1000,
                    ];
                }
                $configVendedores = [];
                foreach ($usuarios as $datos) {
                    $departamento = $departamentos->get($datos['departamento']);
                    $configVendedores[$datos['registro']->usr_id] = [
                        'habilitado' => true,
                        'numero' => $datos['numero'],
                        'departamento_id' => $departamento->cmd_id,
                        'meta' => 1000,
                        'tasa' => $datos['tasa'],
                        'motivo' => $datos['motivo'],
                    ];
                }
                $periodo = $comisiones->guardar([
                    'periodo' => $periodoTexto,
                    'almacen_ids' => $almacenes->pluck('alm_id')->map(fn ($id) => (int) $id)->all(),
                    'motivo_cambio' => self::MARCA.'; escenario repetible para validación con cliente.',
                    'departamentos' => $configDepartamentos,
                    'vendedores' => $configVendedores,
                ], $sucursalId, (int) $admin->usr_id);

                $skuRopa = $this->skuParaDepartamento($departamentos->get('ROPA'));
                $skuTelas = $this->skuParaDepartamento($departamentos->get('TELAS'));
                [$cajaA, $sesionA] = $this->cajaSesion($almacenes->get('I. Suriana'), $admin, 'A');
                [$cajaB, $sesionB] = $this->cajaSesion($almacenes->get('La I. Suriana'), $admin, 'B');
                $inicio = now()->startOfMonth()->setTime(12, 0);

                $anaOrigen = $this->venta($almacenes->get('I. Suriana'), $cajaA, $sesionA, $admin, $skuRopa, $usuarios['comision.ana']['registro'], 600, 'ANA-A', $inicio);
                $this->venta($almacenes->get('La I. Suriana'), $cajaB, $sesionB, $admin, $skuRopa, $usuarios['comision.ana']['registro'], 500, 'ANA-B', $inicio->copy()->addDay());
                $this->devolucion($almacenes->get('I. Suriana'), $cajaA, $sesionA, $admin, $anaOrigen, 100, 'ANA-DEV', $inicio->copy()->addDays(2));
                $this->venta($almacenes->get('I. Suriana'), $cajaA, $sesionA, $admin, $skuRopa, $usuarios['comision.bruno']['registro'], 850, 'BRUNO', $inicio->copy()->addDays(3));
                $this->venta($almacenes->get('La I. Suriana'), $cajaB, $sesionB, $admin, $skuTelas, $usuarios['comision.carla']['registro'], 999.90, 'CARLA', $inicio->copy()->addDays(4));
                $this->venta($almacenes->get('I. Suriana'), $cajaA, $sesionA, $admin, $skuTelas, $usuarios['comision.diego']['registro'], 700, 'DIEGO-A', $inicio->copy()->addDays(5));
                $this->venta($almacenes->get('La I. Suriana'), $cajaB, $sesionB, $admin, $skuTelas, $usuarios['comision.diego']['registro'], 500, 'DIEGO-B', $inicio->copy()->addDays(6));

                if ($periodo->cmp_estatus === 'borrador') {
                    $periodo = $comisiones->aprobar($periodo, (int) $admin->usr_id);
                }

                return $comisiones->estimacionAdministrativa($periodo->fresh());
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Escenario V2 aprobado para '.now()->format('Y-m').'.');
        $this->table(
            ['Usuario', 'Ventas netas', 'Cumplimiento', 'Tasa', 'Comisión estimada'],
            $estimaciones->map(function ($fila) {
                $usuario = collect(self::USUARIOS)->search(fn ($datos) => $datos['nombre'] === $fila->nombre);
                return [
                    $usuario,
                    '$'.number_format($fila->ventas, 2),
                    number_format($fila->cumplimiento, 2).'%',
                    number_format($fila->tasa, 1).'%',
                    '$'.number_format($fila->comision, 2),
                ];
            })->all(),
        );
        $this->line('Contraseña de los cuatro vendedores: '.self::PASSWORD);
        $this->comment('Para retirarlo: php artisan demo:comisiones-v2 --limpiar');

        return self::SUCCESS;
    }

    private function skuParaDepartamento(ComisionV2Departamento $departamento): ProductoSku
    {
        return ProductoSku::query()
            ->where('psk_estatus', 'activo')->where('psk_deleted', false)
            ->whereHas('producto', fn ($query) => $query
                ->whereIn('prd_lna_id', $departamento->lineas->pluck('lna_id'))
                ->where('prd_estatus', 'activo')->where('prd_deleted', false))
            ->orderBy('psk_id')->first()
            ?? throw new RuntimeException("No hay un SKU activo para {$departamento->cmd_nombre}.");
    }

    private function cajaSesion(Almacen $almacen, Usuario $admin, string $sufijo): array
    {
        $caja = Caja::query()->updateOrCreate(
            ['caj_scl_id' => $almacen->alm_scl_id, 'caj_clave' => self::CLAVE_CAJA_PREFIJO.$sufijo],
            [
                'caj_alm_id' => $almacen->alm_id,
                'caj_nombre' => 'Caja demo comisiones V2 '.$sufijo,
                'caj_estatus' => 'activo',
                'caj_updated_by_usr_id' => $admin->usr_id,
            ],
        );
        $caja->forceFill(['caj_deleted' => false, 'caj_deleted_at' => null])->save();
        $sesion = CajaSesion::query()->updateOrCreate(
            ['cse_caj_id' => $caja->caj_id, 'cse_estatus' => 'activa'],
            [
                'cse_scl_id' => $almacen->alm_scl_id,
                'cse_usr_apertura_id' => $admin->usr_id,
                'cse_monto_apertura' => 0,
                'cse_abierta_at' => now()->startOfMonth()->setTime(9, 0),
                'cse_cerrada_at' => null,
            ],
        );

        return [$caja, $sesion];
    }

    private function venta(Almacen $almacen, Caja $caja, CajaSesion $sesion, Usuario $admin, ProductoSku $sku, Usuario $vendedor, float $importe, string $sufijo, Carbon $fecha): PosVentaDetalle
    {
        $venta = PosVenta::query()->updateOrCreate(
            ['psv_folio' => self::FOLIO_PREFIJO.$fecha->format('Ym').'-'.$sufijo],
            [
                'psv_cse_id' => $sesion->cse_id,
                'psv_caj_id' => $caja->caj_id,
                'psv_scl_id' => $almacen->alm_scl_id,
                'psv_alm_id' => $almacen->alm_id,
                'psv_usr_id' => $admin->usr_id,
                'psv_tipo_operacion' => 'venta',
                'psv_estatus' => 'cobrada',
                'psv_subtotal' => $importe,
                'psv_descuento' => 0,
                'psv_total' => $importe,
                'psv_pagado' => $importe,
                'psv_cambio' => 0,
                'psv_notas' => self::MARCA,
                'psv_fecha_cobro' => $fecha,
                'psv_updated_by_usr_id' => $admin->usr_id,
            ],
        );
        $venta->forceFill(['psv_deleted' => false, 'psv_deleted_at' => null])->save();

        return PosVentaDetalle::query()->updateOrCreate(
            ['pvd_psv_id' => $venta->psv_id, 'pvd_psk_id' => $sku->psk_id],
            [
                'pvd_alm_id' => $almacen->alm_id,
                'pvd_cantidad' => 1,
                'pvd_precio_unitario' => $importe,
                'pvd_descuento_porcentaje' => 0,
                'pvd_descuento_importe' => 0,
                'pvd_importe' => $importe,
                'pvd_usr_id' => $vendedor->usr_id,
                'pvd_updated_by_usr_id' => $admin->usr_id,
            ],
        );
    }

    private function devolucion(Almacen $almacen, Caja $caja, CajaSesion $sesion, Usuario $admin, PosVentaDetalle $origen, float $importe, string $sufijo, Carbon $fecha): void
    {
        $cambio = PosVenta::query()->updateOrCreate(
            ['psv_folio' => self::FOLIO_PREFIJO.$fecha->format('Ym').'-'.$sufijo],
            [
                'psv_cse_id' => $sesion->cse_id,
                'psv_caj_id' => $caja->caj_id,
                'psv_scl_id' => $almacen->alm_scl_id,
                'psv_alm_id' => $almacen->alm_id,
                'psv_usr_id' => $admin->usr_id,
                'psv_tipo_operacion' => 'cambio',
                'psv_venta_origen_id' => $origen->pvd_psv_id,
                'psv_estatus' => 'cobrada',
                'psv_subtotal' => 0,
                'psv_descuento' => 0,
                'psv_credito_cambio' => $importe,
                'psv_total' => 0,
                'psv_pagado' => 0,
                'psv_cambio' => 0,
                'psv_notas' => self::MARCA,
                'psv_fecha_cobro' => $fecha,
                'psv_updated_by_usr_id' => $admin->usr_id,
            ],
        );
        $cambio->forceFill(['psv_deleted' => false, 'psv_deleted_at' => null])->save();
        PosCambioDetalle::query()->updateOrCreate(
            ['pcd_psv_id' => $cambio->psv_id, 'pcd_pvd_origen_id' => $origen->pvd_id],
            [
                'pcd_psv_origen_id' => $origen->pvd_psv_id,
                'pcd_psk_id' => $origen->pvd_psk_id,
                'pcd_alm_id' => $almacen->alm_id,
                'pcd_cantidad' => 1,
                'pcd_precio_unitario' => $importe,
                'pcd_importe_credito' => $importe,
                'pcd_condicion' => 'reventa',
                'pcd_updated_by_usr_id' => $admin->usr_id,
            ],
        );
    }

    private function limpiar(): int
    {
        DB::transaction(function (): void {
            $ventas = PosVenta::query()->where('psv_folio', 'like', self::FOLIO_PREFIJO.'%')->pluck('psv_id');
            PosCambioDetalle::query()->whereIn('pcd_psv_id', $ventas)->delete();
            PosVentaDetalle::query()->whereIn('pvd_psv_id', $ventas)->delete();
            PosVenta::query()->whereIn('psv_id', $ventas)->whereNotNull('psv_venta_origen_id')->forceDelete();
            PosVenta::query()->whereIn('psv_id', $ventas)->forceDelete();

            ComisionV2Periodo::query()
                ->whereDate('cmp_periodo', now()->startOfMonth()->toDateString())
                ->where('cmp_ultimo_motivo_cambio', 'like', self::MARCA.'%')
                ->delete();

            $cajas = Caja::query()->where('caj_clave', 'like', self::CLAVE_CAJA_PREFIJO.'%')->pluck('caj_id');
            CajaSesion::query()->whereIn('cse_caj_id', $cajas)->delete();
            Caja::query()->whereIn('caj_id', $cajas)->forceDelete();

            $usuarios = Usuario::query()->whereIn('usr_usuario', array_keys(self::USUARIOS))->pluck('usr_id');
            DB::table('personal_access_tokens')->where('tokenable_type', Usuario::class)->whereIn('tokenable_id', $usuarios)->delete();
            UsuarioRol::query()->whereIn('url_usr_id', $usuarios)->delete();
            UsuarioSucursal::query()->whereIn('usc_usr_id', $usuarios)->delete();
            Usuario::query()->whereIn('usr_id', $usuarios)->forceDelete();
        });

        $this->info('Escenario demostrativo V2 eliminado. Los datos históricos anteriores no se tocaron.');
        return self::SUCCESS;
    }
}
