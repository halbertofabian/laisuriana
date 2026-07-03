<?php

namespace Tests\Feature\Operacion;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\CajaSesionUsuario;
use App\Models\CajaUsuario;
use App\Models\ExistenciaAlmacen;
use App\Models\PosCambioDetalle;
use App\Models\PosCorteCaja;
use App\Models\PosVenta;
use App\Models\ProductoSku;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_puede_registrar_retiro_de_caja_y_aparece_en_resumen_separado(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos(100);
        $autorizador = $this->crearUsuarioAutorizadorCorte($sucursal->scl_id);
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();

        $this->setExistencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id, 5);
        $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $sku->psk_id, 349.90);

        $response = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.retiros.store'), [
                'monto' => 200,
                'referencia' => 'Resguardo nocturno',
                'motivo' => 'Retiro preventivo por excedente de efectivo.',
                'autoriza_usr_id' => $autorizador->usr_id,
                'autoriza_password' => 'Corte12345',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('tbl_caja_movimientos_cjm', [
            'cjm_tipo' => 'retiro',
            'cjm_monto' => 200,
            'cjm_referencia' => 'Resguardo nocturno',
        ]);

        $resumen = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->getJson(route('pos.ventas.dia'));

        $resumen->assertOk();
        $this->assertEquals(200.0, (float) $resumen->json('resumen.retiros'));
        $this->assertEquals(0.0, (float) $resumen->json('resumen.gastos'));
        $this->assertEquals(249.9, (float) $resumen->json('resumen.efectivo_disponible'));

        $movimiento = CajaMovimiento::query()->where('cjm_tipo', 'retiro')->firstOrFail();
        $ticket = $this->actingAs($admin)->get(route('pos.caja.movimientos.ticket', $movimiento));
        $ticket->assertOk();
        $ticket->assertHeader('content-type', 'application/pdf');
    }

    public function test_puede_registrar_gasto_de_caja_y_valida_efectivo_disponible(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos(50);
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();

        $this->setExistencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id, 3);
        $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $sku->psk_id, 129.50);

        $ok = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.gastos.store'), [
                'monto' => 40,
                'categoria' => 'Papelería',
                'motivo' => 'Compra de insumo operativo.',
            ]);

        $ok->assertOk();
        $this->assertDatabaseHas('tbl_caja_movimientos_cjm', [
            'cjm_tipo' => 'gasto',
            'cjm_categoria' => 'Papelería',
            'cjm_monto' => 40,
        ]);

        $excede = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.gastos.store'), [
                'monto' => 500,
                'categoria' => 'Papelería',
                'motivo' => 'No debería permitirlo.',
            ]);

        $excede->assertStatus(422);
        $excede->assertJsonValidationErrors(['monto']);
    }

    public function test_puede_realizar_corte_de_caja_y_cierra_la_sesion(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos(150);
        $autorizador = $this->crearUsuarioAutorizadorCorte($sucursal->scl_id);
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();

        $this->setExistencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id, 6);
        $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $sku->psk_id, 349.90);

        $gasto = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.gastos.store'), [
                'monto' => 20,
                'categoria' => 'Limpieza',
                'motivo' => 'Compra de insumo de limpieza.',
            ]);

        $gasto->assertOk();

        $response = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.cortes.store'), [
                'denominaciones' => [
                    '1000' => 0,
                    '500' => 0,
                    '200' => 2,
                    '100' => 0,
                    '50' => 1,
                    '20' => 1,
                ],
                'cambio' => 9.90,
                'observaciones' => 'Cierre sin incidencias.',
                'autoriza_usr_id' => $autorizador->usr_id,
                'autoriza_password' => 'Corte12345',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.pco_efectivo_esperado', 479.9);
        $response->assertJsonPath('data.pco_efectivo_reportado', 479.9);
        $this->assertEquals(0.0, (float) $response->json('data.pco_diferencia'));

        $corte = PosCorteCaja::query()->firstOrFail();
        $this->assertSame('cerrado', $corte->pco_estado);
        $this->assertDatabaseHas('tbl_pos_corte_denominaciones_pdn', [
            'pdn_pco_id' => $corte->pco_id,
            'pdn_clave' => '200',
            'pdn_cantidad_piezas' => 2,
            'pdn_monto' => 400,
        ]);
        $this->assertDatabaseHas('tbl_pos_corte_denominaciones_pdn', [
            'pdn_pco_id' => $corte->pco_id,
            'pdn_clave' => 'cambio',
            'pdn_monto' => 9.90,
        ]);
        $this->assertDatabaseHas('tbl_caja_sesiones_cse', [
            'cse_id' => $corte->pco_cse_id,
            'cse_estatus' => 'cerrada',
        ]);
        $this->assertDatabaseMissing('tbl_caja_sesion_usuarios_csu', [
            'csu_cse_id' => $corte->pco_cse_id,
            'csu_usr_id' => $admin->usr_id,
            'csu_estatus' => 'activo',
            'csu_salida_at' => null,
        ]);

        $ventaPosterior = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.ventas.cobrar'), [
                'almacen_id' => $almacen->alm_id,
                'metodo_pago' => 'efectivo',
                'monto_efectivo' => 129.50,
                'items' => [
                    [
                        'psk_id' => $sku->psk_id,
                        'cantidad' => 1,
                        'precio' => 129.50,
                    ],
                ],
            ]);

        $ventaPosterior->assertStatus(422);
        $ventaPosterior->assertJsonValidationErrors(['caja']);

        $gastoPosterior = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.gastos.store'), [
                'monto' => 10,
                'categoria' => 'Papelería',
                'motivo' => 'No debe permitirse tras el corte.',
            ]);

        $gastoPosterior->assertStatus(422);
        $gastoPosterior->assertJsonValidationErrors(['caja']);

        $ticket = $this->actingAs($admin)->get(route('pos.caja.cortes.ticket', $corte));
        $ticket->assertOk();
        $ticket->assertHeader('content-type', 'application/pdf');
    }

    public function test_no_cierra_caja_si_la_autorizacion_del_corte_es_invalida(): void
    {
        [$admin, $sucursal, $almacen] = $this->prepararEscenarioPos(50);
        $autorizador = $this->crearUsuarioAutorizadorCorte($sucursal->scl_id);
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();

        $this->setExistencia($sku->psk_id, $sucursal->scl_id, $almacen->alm_id, 3);
        $this->crearVentaBase($admin, $sucursal->scl_id, $almacen->alm_id, $sku->psk_id, 129.50);

        $response = $this->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->actingAs($admin)
            ->postJson(route('pos.caja.cortes.store'), [
                'denominaciones' => [
                    '1000' => 0,
                    '500' => 0,
                    '200' => 0,
                    '100' => 1,
                    '50' => 1,
                    '20' => 1,
                ],
                'cambio' => 9.50,
                'autoriza_usr_id' => $autorizador->usr_id,
                'autoriza_password' => 'incorrecta',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['autoriza_usr_id']);
        $this->assertDatabaseCount('tbl_pos_cortes_pco', 0);
        $this->assertDatabaseHas('tbl_caja_sesiones_cse', [
            'cse_usr_apertura_id' => $admin->usr_id,
            'cse_estatus' => 'activa',
        ]);
    }

    private function prepararEscenarioPos(float $montoApertura = 0): array
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
            'cse_monto_apertura' => $montoApertura,
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

    private function crearUsuarioAutorizadorCorte(int $sucursalId): Usuario
    {
        $rolSupervisor = Rol::query()->where('rol_nombre', 'Supervisor')->firstOrFail();
        $usuario = Usuario::query()->create([
            'usr_usuario' => 'aut.corte.' . Usuario::query()->count(),
            'usr_password' => Hash::make('Corte12345'),
            'usr_nombre' => 'Autorizador Corte',
            'usr_email' => 'aut.corte.' . now()->timestamp . '@lasuriana.local',
            'usr_estatus' => 'activo',
        ]);

        UsuarioRol::query()->create([
            'url_usr_id' => $usuario->usr_id,
            'url_rol_id' => $rolSupervisor->rol_id,
            'url_estatus' => 'activo',
            'url_deleted' => false,
            'url_deleted_at' => null,
        ]);

        UsuarioSucursal::query()->create([
            'usc_usr_id' => $usuario->usr_id,
            'usc_scl_id' => $sucursalId,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
            'usc_deleted_at' => null,
        ]);

        return $usuario;
    }
}
