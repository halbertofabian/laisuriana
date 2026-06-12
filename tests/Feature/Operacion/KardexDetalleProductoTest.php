<?php

namespace Tests\Feature\Operacion;

use App\Models\ExistenciaAlmacen;
use App\Models\MovimientoInventario;
use App\Models\ProductoSku;
use App\Models\TipoMovimientoInventario;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KardexDetalleProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_ver_kardex_detallado_por_sku(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $tipo = TipoMovimientoInventario::query()->where('tmi_clave', 'inventario.entrada')->firstOrFail();

        ExistenciaAlmacen::query()->create([
            'exa_psk_id' => $sku->psk_id,
            'exa_scl_id' => 1,
            'exa_alm_id' => 1,
            'exa_existencia' => 9,
            'exa_estatus' => 'activo',
        ]);

        MovimientoInventario::query()->create([
            'min_folio' => 'MIN-TEST-001',
            'min_tmi_id' => $tipo->tmi_id,
            'min_psk_id' => $sku->psk_id,
            'min_scl_id' => 1,
            'min_alm_id' => 1,
            'min_documento_tipo' => 'entrada_normal',
            'min_documento_referencia' => 'REF-DET-001',
            'min_cantidad' => 3,
            'min_signo' => 1,
            'min_existencia_antes' => 6,
            'min_existencia_despues' => 9,
            'min_motivo_texto' => 'Prueba kardex detalle',
            'min_estatus' => 'activo',
            'min_es_reversa' => false,
            'min_fecha_movimiento' => now(),
        ]);

        $response = $this->get(route('operacion.inventario_base.kardex.detalle', [
            'sku' => $sku->psk_id,
            'periodo' => 'este_mes',
        ]));

        $response->assertOk();
        $response->assertSee('Kardex Completo');
        $response->assertSee('SKU-POLO-CH-AZM');
        $response->assertSee('REF-DET-001');
    }

    public function test_rango_invalido_muestra_error_en_kardex_detalle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();

        $response = $this->from(route('operacion.inventario_base.kardex.detalle', ['sku' => $sku->psk_id]))
            ->get(route('operacion.inventario_base.kardex.detalle', [
                'sku' => $sku->psk_id,
                'periodo' => 'rango',
                'fecha_inicio' => '2026-06-30',
                'fecha_fin' => '2026-06-01',
            ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['fecha_inicio']);
    }
}
