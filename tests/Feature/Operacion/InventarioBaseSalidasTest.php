<?php

namespace Tests\Feature\Operacion;

use App\Models\Almacen;
use App\Models\ExistenciaAlmacen;
use App\Models\MovimientoInventario;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioBaseSalidasTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_resolver_producto_para_salida_por_codigo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $sucursal = Sucursal::query()->where('scl_estatus', 'activo')->orderBy('scl_id')->firstOrFail();
        $almacen = Almacen::query()->where('alm_scl_id', $sucursal->scl_id)->where('alm_estatus', 'activo')->orderBy('alm_id')->firstOrFail();

        ExistenciaAlmacen::query()->updateOrCreate(
            [
                'exa_psk_id' => $sku->psk_id,
                'exa_scl_id' => $sucursal->scl_id,
                'exa_alm_id' => $almacen->alm_id,
            ],
            [
                'exa_existencia' => 7,
                'exa_estatus' => 'activo',
            ]
        );

        $response = $this->getJson(route('operacion.inventario_base.salidas.resolver', [
            'q' => '7509000100018',
            'min_scl_id' => $sucursal->scl_id,
            'min_alm_id' => $almacen->alm_id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.psk_codigo', 'SKU-POLO-CH-AZM');
        $response->assertJsonPath('data.producto.prd_codigo', 'PRD-POLO-H-001');
        $response->assertJsonPath('data.existencia', 7.0);
    }

    public function test_admin_puede_registrar_salida_por_lote_en_una_sola_operacion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $sucursal = Sucursal::query()->where('scl_estatus', 'activo')->orderBy('scl_id')->firstOrFail();
        $almacen = Almacen::query()->where('alm_scl_id', $sucursal->scl_id)->where('alm_estatus', 'activo')->orderBy('alm_id')->firstOrFail();
        $skuUno = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $skuDos = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-M-AZM')->firstOrFail();

        ExistenciaAlmacen::query()->updateOrCreate(
            ['exa_psk_id' => $skuUno->psk_id, 'exa_scl_id' => $sucursal->scl_id, 'exa_alm_id' => $almacen->alm_id],
            ['exa_existencia' => 5, 'exa_estatus' => 'activo']
        );
        ExistenciaAlmacen::query()->updateOrCreate(
            ['exa_psk_id' => $skuDos->psk_id, 'exa_scl_id' => $sucursal->scl_id, 'exa_alm_id' => $almacen->alm_id],
            ['exa_existencia' => 4, 'exa_estatus' => 'activo']
        );

        $response = $this->postJson(route('operacion.inventario_base.salidas.store'), [
            'min_scl_id' => $sucursal->scl_id,
            'min_alm_id' => $almacen->alm_id,
            'min_documento_tipo' => 'merma',
            'min_fecha_movimiento' => now()->format('Y-m-d H:i:s'),
            'min_motivo_texto' => 'Salida por lector',
            'lineas' => [
                ['min_psk_id' => $skuUno->psk_id, 'min_cantidad' => 2],
                ['min_psk_id' => $skuDos->psk_id, 'min_cantidad' => 1],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.total_lineas', 2);

        $this->assertDatabaseHas('tbl_movimientos_inventario_min', [
            'min_psk_id' => $skuUno->psk_id,
            'min_scl_id' => $sucursal->scl_id,
            'min_alm_id' => $almacen->alm_id,
            'min_cantidad' => 2,
            'min_documento_tipo' => 'merma',
        ]);
        $this->assertDatabaseHas('tbl_movimientos_inventario_min', [
            'min_psk_id' => $skuDos->psk_id,
            'min_scl_id' => $sucursal->scl_id,
            'min_alm_id' => $almacen->alm_id,
            'min_cantidad' => 1,
            'min_documento_tipo' => 'merma',
        ]);

        $this->assertSame(3.0, (float) ExistenciaAlmacen::query()->where('exa_psk_id', $skuUno->psk_id)->where('exa_scl_id', $sucursal->scl_id)->where('exa_alm_id', $almacen->alm_id)->value('exa_existencia'));
        $this->assertSame(3.0, (float) ExistenciaAlmacen::query()->where('exa_psk_id', $skuDos->psk_id)->where('exa_scl_id', $sucursal->scl_id)->where('exa_alm_id', $almacen->alm_id)->value('exa_existencia'));
    }

    public function test_salida_por_lote_no_deja_movimientos_parciales_si_falla_una_linea(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $sucursal = Sucursal::query()->where('scl_estatus', 'activo')->orderBy('scl_id')->firstOrFail();
        $almacen = Almacen::query()->where('alm_scl_id', $sucursal->scl_id)->where('alm_estatus', 'activo')->orderBy('alm_id')->firstOrFail();
        $skuUno = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $skuDos = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-M-AZM')->firstOrFail();

        ExistenciaAlmacen::query()->updateOrCreate(
            ['exa_psk_id' => $skuUno->psk_id, 'exa_scl_id' => $sucursal->scl_id, 'exa_alm_id' => $almacen->alm_id],
            ['exa_existencia' => 5, 'exa_estatus' => 'activo']
        );
        ExistenciaAlmacen::query()->updateOrCreate(
            ['exa_psk_id' => $skuDos->psk_id, 'exa_scl_id' => $sucursal->scl_id, 'exa_alm_id' => $almacen->alm_id],
            ['exa_existencia' => 0, 'exa_estatus' => 'activo']
        );

        $response = $this->postJson(route('operacion.inventario_base.salidas.store'), [
            'min_scl_id' => $sucursal->scl_id,
            'min_alm_id' => $almacen->alm_id,
            'min_documento_tipo' => 'merma',
            'min_fecha_movimiento' => now()->format('Y-m-d H:i:s'),
            'min_motivo_texto' => 'Salida por lector',
            'lineas' => [
                ['min_psk_id' => $skuUno->psk_id, 'min_cantidad' => 1],
                ['min_psk_id' => $skuDos->psk_id, 'min_cantidad' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['min_cantidad']);

        $this->assertSame(5.0, (float) ExistenciaAlmacen::query()->where('exa_psk_id', $skuUno->psk_id)->where('exa_scl_id', $sucursal->scl_id)->where('exa_alm_id', $almacen->alm_id)->value('exa_existencia'));
        $this->assertSame(0.0, (float) ExistenciaAlmacen::query()->where('exa_psk_id', $skuDos->psk_id)->where('exa_scl_id', $sucursal->scl_id)->where('exa_alm_id', $almacen->alm_id)->value('exa_existencia'));
        $this->assertSame(0, MovimientoInventario::query()->count());
    }
}
