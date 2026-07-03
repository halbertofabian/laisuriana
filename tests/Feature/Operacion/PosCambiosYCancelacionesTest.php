<?php

namespace Tests\Feature\Operacion;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\CajaSesionUsuario;
use App\Models\CajaUsuario;
use App\Models\ExistenciaAlmacen;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCambiosYCancelacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_cancelar_una_venta_pos_y_revertir_inventario(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos();
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();

        $this->setExistencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id, 5);

        $ventaResponse = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.ventas.cobrar'), [
                'almacen_id' => $almacen->alm_id,
                'metodo_pago' => 'efectivo',
                'monto_efectivo' => 349.90,
                'items' => [
                    [
                        'psk_id' => $sku->psk_id,
                        'cantidad' => 1,
                        'precio' => 349.90,
                    ],
                ],
            ]);

        $ventaResponse->assertOk();
        $ventaId = (int) $ventaResponse->json('data.psv_id');
        $venta = PosVenta::query()->findOrFail($ventaId);

        $this->assertSame(4.0, $this->existencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id));

        $cancelResponse = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.ventas.cancelar', $venta), [
                'motivo' => 'Cliente solicitó anulación inmediata.',
            ]);

        $cancelResponse->assertOk();
        $cancelResponse->assertJsonPath('data.psv_estatus', 'cancelada');

        $venta->refresh();
        $movimientoOriginalId = (int) \App\Models\MovimientoInventario::query()
            ->where('min_documento_referencia', $venta->psv_folio)
            ->where('min_signo', -1)
            ->value('min_id');

        $this->assertSame('cancelada', $venta->psv_estatus);
        $this->assertSame(5.0, $this->existencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id));
        $this->assertDatabaseHas('tbl_movimientos_inventario_min', [
            'min_documento_referencia' => $venta->psv_folio,
            'min_signo' => -1,
            'min_estatus' => 'cancelado',
        ]);
        $this->assertDatabaseHas('tbl_movimientos_inventario_min', [
            'min_documento_tipo' => 'cancelacion',
            'min_reversa_de_min_id' => $movimientoOriginalId,
        ]);
    }

    public function test_no_permite_cambio_si_el_nuevo_carrito_es_de_menor_valor(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos();
        $skuVenta = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $skuMenor = ProductoSku::query()->where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();

        $this->setExistencia($skuVenta->psk_id, $sucursal->scl_id, $almacen->alm_id, 3);
        $this->setExistencia($skuMenor->psk_id, $sucursal->scl_id, $almacen->alm_id, 10);

        $venta = $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $skuVenta->psk_id, 349.90);
        $detalleVenta = $venta->detalle()->firstOrFail();

        $response = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.cambios.store'), [
                'almacen_id' => $almacen->alm_id,
                'venta_origen_id' => $venta->psv_id,
                'metodo_pago' => 'sin_pago',
                'monto_efectivo' => 0,
                'monto_tarjeta' => 0,
                'items' => [
                    [
                        'psk_id' => $skuMenor->psk_id,
                        'cantidad' => 1,
                        'precio' => 129.50,
                    ],
                ],
                'devoluciones' => [
                    [
                        'pvd_id' => $detalleVenta->pvd_id,
                        'cantidad' => 1,
                        'condicion' => 'reventa',
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
    }

    public function test_puede_registrar_cambio_sin_reembolso_por_mismo_valor(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos();
        $skuDevuelto = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $skuNuevo = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-M-AZM')->firstOrFail();

        $this->setExistencia($skuDevuelto->psk_id, $sucursal->scl_id, $almacen->alm_id, 2);
        $this->setExistencia($skuNuevo->psk_id, $sucursal->scl_id, $almacen->alm_id, 4);

        $venta = $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $skuDevuelto->psk_id, 349.90);
        $detalleVenta = $venta->detalle()->firstOrFail();

        $response = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.cambios.store'), [
                'almacen_id' => $almacen->alm_id,
                'venta_origen_id' => $venta->psv_id,
                'metodo_pago' => 'sin_pago',
                'monto_efectivo' => 0,
                'monto_tarjeta' => 0,
                'items' => [
                    [
                        'psk_id' => $skuNuevo->psk_id,
                        'cantidad' => 1,
                        'precio' => 349.90,
                    ],
                ],
                'devoluciones' => [
                    [
                        'pvd_id' => $detalleVenta->pvd_id,
                        'cantidad' => 1,
                        'condicion' => 'reventa',
                    ],
                ],
            ]);

        $response->assertOk();
        $this->assertEquals(0.0, (float) $response->json('data.psv_total'));
        $this->assertEquals(349.9, (float) $response->json('data.psv_credito_cambio'));

        $cambio = PosVenta::query()->where('psv_tipo_operacion', 'cambio')->firstOrFail();
        $this->assertSame((int) $venta->psv_id, (int) $cambio->psv_venta_origen_id);
        $this->assertSame(2.0, $this->existencia($skuDevuelto->psk_id, $sucursal->scl_id, $almacen->alm_id));
        $this->assertSame(3.0, $this->existencia($skuNuevo->psk_id, $sucursal->scl_id, $almacen->alm_id));
        $this->assertSame(1, PosCambioDetalle::query()->count());
        $this->assertDatabaseHas('tbl_pos_cambios_detalle_pcd', [
            'pcd_psv_id' => $cambio->psv_id,
            'pcd_pvd_origen_id' => $detalleVenta->pvd_id,
            'pcd_condicion' => 'reventa',
        ]);
    }

    private function prepararEscenarioPos(): array
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->where('scl_estatus', 'activo')->orderBy('scl_id')->firstOrFail();
        $almacen = Almacen::query()
            ->where('alm_scl_id', $sucursal->scl_id)
            ->where('alm_estatus', 'activo')
            ->orderBy('alm_id')
            ->first();

        if (!$almacen) {
            $tipoAlmacen = TipoAlmacen::query()->where('tal_clave', 'principal')->firstOrFail();
            $almacen = Almacen::query()->create([
                'alm_scl_id' => $sucursal->scl_id,
                'alm_tal_id' => $tipoAlmacen->tal_id,
                'alm_nombre' => 'Almacén POS Test',
                'alm_clave' => 'ALM-POS-TEST',
                'alm_estatus' => 'activo',
            ]);
        }

        $caja = Caja::query()->create([
            'caj_scl_id' => $sucursal->scl_id,
            'caj_alm_id' => $almacen->alm_id,
            'caj_nombre' => 'Caja POS Test',
            'caj_clave' => 'CAJA-POS-TEST',
            'caj_estatus' => 'activo',
        ]);

        CajaUsuario::query()->create([
            'cju_caj_id' => $caja->caj_id,
            'cju_usr_id' => $admin->usr_id,
            'cju_estatus' => 'activo',
            'cju_deleted' => false,
            'cju_deleted_at' => null,
        ]);

        $sesion = CajaSesion::query()->create([
            'cse_caj_id' => $caja->caj_id,
            'cse_scl_id' => $sucursal->scl_id,
            'cse_usr_apertura_id' => $admin->usr_id,
            'cse_monto_apertura' => 0,
            'cse_abierta_at' => now(),
            'cse_estatus' => 'activa',
        ]);

        CajaSesionUsuario::query()->create([
            'csu_cse_id' => $sesion->cse_id,
            'csu_usr_id' => $admin->usr_id,
            'csu_ingreso_at' => now(),
            'csu_estatus' => 'activo',
        ]);

        return [$admin, $sucursal, $almacen];
    }

    private function crearVentaBase(Usuario $admin, int $sucursalId, int $almacenId, int $skuId, float $precio): PosVenta
    {
        $response = $this->withSession(['sucursal_activa_id' => $sucursalId])
            ->actingAs($admin)
            ->postJson(route('pos.ventas.cobrar'), [
                'almacen_id' => $almacenId,
                'metodo_pago' => 'efectivo',
                'monto_efectivo' => $precio,
                'items' => [
                    [
                        'psk_id' => $skuId,
                        'cantidad' => 1,
                        'precio' => $precio,
                    ],
                ],
            ]);

        $response->assertOk();

        return PosVenta::query()->findOrFail((int) $response->json('data.psv_id'));
    }

    private function setExistencia(int $skuId, int $sucursalId, int $almacenId, float $existencia): void
    {
        ExistenciaAlmacen::query()->updateOrCreate(
            [
                'exa_psk_id' => $skuId,
                'exa_scl_id' => $sucursalId,
                'exa_alm_id' => $almacenId,
            ],
            [
                'exa_existencia' => $existencia,
                'exa_estatus' => 'activo',
            ]
        );
    }

    private function existencia(int $skuId, int $sucursalId, int $almacenId): float
    {
        return (float) ExistenciaAlmacen::query()
            ->where('exa_psk_id', $skuId)
            ->where('exa_scl_id', $sucursalId)
            ->where('exa_alm_id', $almacenId)
            ->value('exa_existencia');
    }
}
