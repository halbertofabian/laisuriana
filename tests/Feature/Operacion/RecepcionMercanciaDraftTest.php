<?php

namespace Tests\Feature\Operacion;

use App\Models\Almacen;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\RecepcionMercancia;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecepcionMercanciaDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_borrador_persiste_sin_generar_movimientos_definitivos(): void
    {
        $contexto = $this->seedearContextoRecepcion();
        $movimientosAntes = MovimientoInventario::query()->count();

        $response = $this->actingAs($contexto['admin'])
            ->postJson(route('operacion.inventario_base.recepciones.borrador.store'), $this->payloadRecepcion($contexto));

        $response->assertOk();

        $recepcionId = (int) data_get($response->json(), 'data.rme_id');
        $this->assertGreaterThan(0, $recepcionId);
        $this->assertSame($movimientosAntes, MovimientoInventario::query()->count());

        $this->assertDatabaseHas('tbl_recepciones_mercancia_rme', [
            'rme_id' => $recepcionId,
            'rme_estado' => RecepcionMercancia::ESTADO_BORRADOR,
        ]);
        $this->assertDatabaseHas('tbl_recepcion_mercancia_detalle_rmd', [
            'rmd_rme_id' => $recepcionId,
            'rmd_psk_id' => $contexto['sku']->psk_id,
            'rmd_cantidad' => 3,
        ]);
    }

    public function test_confirmar_borrador_cambia_estado_y_registra_movimiento(): void
    {
        $contexto = $this->seedearContextoRecepcion();

        $draftResponse = $this->actingAs($contexto['admin'])
            ->postJson(route('operacion.inventario_base.recepciones.borrador.store'), $this->payloadRecepcion($contexto));
        $draftResponse->assertOk();
        $recepcionId = (int) data_get($draftResponse->json(), 'data.rme_id');

        $movimientosAntes = MovimientoInventario::query()->count();

        $confirmResponse = $this->actingAs($contexto['admin'])
            ->postJson(route('operacion.inventario_base.recepciones.confirmar'), array_merge(
                $this->payloadRecepcion($contexto),
                ['rme_id' => $recepcionId],
            ));

        $confirmResponse->assertOk();
        $this->assertDatabaseHas('tbl_recepciones_mercancia_rme', [
            'rme_id' => $recepcionId,
            'rme_estado' => RecepcionMercancia::ESTADO_FINALIZADO,
        ]);
        $this->assertSame($movimientosAntes + 1, MovimientoInventario::query()->count());
        $this->assertDatabaseHas('tbl_movimientos_inventario_min', [
            'min_rme_id' => $recepcionId,
            'min_psk_id' => $contexto['sku']->psk_id,
            'min_signo' => 1,
            'min_estatus' => 'activo',
        ]);
    }

    public function test_listado_de_recepciones_permite_filtrar_por_estado(): void
    {
        $contexto = $this->seedearContextoRecepcion();

        $draftResponse = $this->actingAs($contexto['admin'])
            ->postJson(route('operacion.inventario_base.recepciones.borrador.store'), $this->payloadRecepcion($contexto, 2));
        $draftResponse->assertOk();

        $finalPayload = $this->payloadRecepcion($contexto, 5);
        $confirmResponse = $this->actingAs($contexto['admin'])
            ->postJson(route('operacion.inventario_base.recepciones.confirmar'), $finalPayload);
        $confirmResponse->assertOk();

        $draftList = $this->actingAs($contexto['admin'])
            ->getJson(route('operacion.inventario_base.recepciones.data', ['estado' => 'borrador']));
        $draftList->assertOk();
        $this->assertCount(1, $draftList->json('data'));
        $this->assertSame('borrador', data_get($draftList->json(), 'data.0.rme_estado'));

        $finalList = $this->actingAs($contexto['admin'])
            ->getJson(route('operacion.inventario_base.recepciones.data', ['estado' => 'finalizado']));
        $finalList->assertOk();
        $this->assertCount(1, $finalList->json('data'));
        $this->assertSame('finalizado', data_get($finalList->json(), 'data.0.rme_estado'));
    }

    private function seedearContextoRecepcion(): array
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sku = ProductoSku::query()
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->where('psk_estatus', 'activo')
            ->firstOrFail();
        $producto = Producto::query()->findOrFail($sku->psk_prd_id);
        $sucursal = Sucursal::query()
            ->where('scl_deleted', false)
            ->whereNull('scl_deleted_at')
            ->where('scl_estatus', 'activo')
            ->firstOrFail();
        $tipoAlmacen = TipoAlmacen::query()->firstOrCreate(
            ['tal_clave' => 'pruebas'],
            [
                'tal_nombre' => 'Pruebas',
                'tal_descripcion' => 'Tipo de almacén de pruebas',
                'tal_estatus' => 'activo',
                'tal_created_by_usr_id' => $admin->usr_id,
                'tal_updated_by_usr_id' => $admin->usr_id,
            ]
        );
        $almacen = Almacen::query()->firstOrCreate(
            [
                'alm_scl_id' => $sucursal->scl_id,
                'alm_clave' => 'QA-REC',
            ],
            [
                'alm_tal_id' => $tipoAlmacen->tal_id,
                'alm_nombre' => 'Almacén QA Recepción',
                'alm_estatus' => 'activo',
                'alm_created_by_usr_id' => $admin->usr_id,
                'alm_updated_by_usr_id' => $admin->usr_id,
            ]
        );

        return [
            'admin' => $admin,
            'sku' => $sku,
            'producto' => $producto,
            'almacen' => $almacen,
        ];
    }

    private function payloadRecepcion(array $contexto, int $cantidad = 3): array
    {
        return [
            'min_scl_id' => (int) $contexto['almacen']->alm_scl_id,
            'min_alm_id' => (int) $contexto['almacen']->alm_id,
            'min_fecha_movimiento' => now()->format('Y-m-d H:i:s'),
            'min_documento_tipo' => 'entrada_normal',
            'min_documento_referencia' => 'BORRADOR-QA',
            'min_motivo_texto' => 'Recepción de mercancía manual',
            'min_observaciones' => 'Prueba automatizada de borrador.',
            'min_descuento_tipo' => 'ninguno',
            'min_descuento_valor' => 0,
            'min_flete_total' => 0,
            'min_iva_porcentaje' => 0,
            'lineas' => [[
                'prd_id' => (int) $contexto['producto']->prd_id,
                'min_psk_id' => (int) $contexto['sku']->psk_id,
                'min_cantidad' => $cantidad,
                'min_precio_unitario' => 125,
            ]],
            'payload' => [
                'productos' => [[
                    'prd_id' => (int) $contexto['producto']->prd_id,
                    'prd_tipo' => (string) $contexto['producto']->prd_tipo,
                    'prd_codigo' => (string) $contexto['producto']->prd_codigo,
                    'prd_nombre' => (string) $contexto['producto']->prd_nombre,
                ]],
            ],
        ];
    }
}
