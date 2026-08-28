<?php

namespace Tests\Feature\Api;

use App\Models\Almacen;
use App\Models\PedidoPiso;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileFloorOrderTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Sucursal $sucursal;
    private Almacen $almacenPrincipal;
    private Almacen $almacenSecundario;
    private ProductoSku $skuPieza;
    private ProductoSku $skuDecimal;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->usuario = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->sucursal = Sucursal::query()->where('scl_clave', 'MATRIZ')->firstOrFail();
        $this->almacenPrincipal = Almacen::query()->where('alm_scl_id', $this->sucursal->scl_id)->firstOrFail();
        $this->almacenSecundario = Almacen::query()->create([
            'alm_scl_id' => $this->sucursal->scl_id,
            'alm_tal_id' => TipoAlmacen::query()->where('tal_clave', 'principal')->value('tal_id'),
            'alm_nombre' => 'Almacén Móvil Secundario',
            'alm_clave' => 'ALM-MOBILE-SEC',
            'alm_estatus' => 'activo',
        ]);
        $this->skuPieza = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $this->skuDecimal = ProductoSku::query()->where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();
        $this->assignProduct($this->skuPieza, $this->almacenPrincipal);
        $this->assignProduct($this->skuDecimal, $this->almacenSecundario);
        $this->token = $this->usuario->createToken('mobile-order-test', ['mobile:orders'])->plainTextToken;
    }

    public function test_genera_un_pedido_por_almacen_con_precio_y_vendedor_del_servidor(): void
    {
        $requestId = (string) Str::uuid();

        $response = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => $requestId,
            'branch_id' => $this->sucursal->scl_id,
            'notes' => 'Entregar ambos tickets al cliente.',
            'lines' => [
                [
                    'sku_id' => $this->skuPieza->psk_id,
                    'warehouse_id' => $this->almacenPrincipal->alm_id,
                    'quantity' => 2,
                ],
                [
                    'sku_id' => $this->skuDecimal->psk_id,
                    'warehouse_id' => $this->almacenSecundario->alm_id,
                    'quantity' => 1.5,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.request_id', $requestId)
            ->assertJsonCount(2, 'data.orders')
            ->assertJsonPath('data.orders.0.status', 'pending');

        $this->assertDatabaseCount('tbl_pedidos_piso_pdp', 2);
        $this->assertDatabaseHas('tbl_pedidos_piso_pdp', [
            'pdp_mobile_request_id' => $requestId,
            'pdp_usr_id' => $this->usuario->usr_id,
            'pdp_alm_id' => $this->almacenPrincipal->alm_id,
            'pdp_total' => 699.80,
            'pdp_estatus' => 'pendiente_cobro',
        ]);
        $this->assertDatabaseHas('tbl_pedidos_piso_pdp', [
            'pdp_mobile_request_id' => $requestId,
            'pdp_alm_id' => $this->almacenSecundario->alm_id,
            'pdp_total' => 194.25,
        ]);
        $this->assertDatabaseHas('tbl_pedido_piso_detalle_ppd', [
            'ppd_psk_id' => $this->skuPieza->psk_id,
            'ppd_precio_unitario' => 349.90,
            'ppd_usr_id' => $this->usuario->usr_id,
        ]);
    }

    public function test_reintento_con_el_mismo_request_id_no_duplica_pedidos(): void
    {
        $requestId = (string) Str::uuid();
        $payload = [
            'request_id' => $requestId,
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ];

        $first = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', $payload)->assertCreated();
        $second = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', $payload)->assertCreated();

        $this->assertDatabaseCount('tbl_pedidos_piso_pdp', 1);
        $this->assertSame($first->json('data.orders.0.id'), $second->json('data.orders.0.id'));
        $this->assertSame($first->json('data.orders.0.folio'), $second->json('data.orders.0.folio'));
    }

    public function test_lista_y_detalle_solo_incluyen_pedidos_del_vendedor(): void
    {
        $requestId = (string) Str::uuid();
        $created = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => $requestId,
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 3,
            ]],
        ])->assertCreated();
        $pedidoId = (int) $created->json('data.orders.0.id');

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pedidoId)
            ->assertJsonPath('data.0.item_count', 3);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders/' . $pedidoId)
            ->assertOk()
            ->assertJsonPath('data.id', $pedidoId)
            ->assertJsonPath('data.lines.0.quantity', 3)
            ->assertJsonPath('data.lines.0.price', 349.9);

    }

    public function test_lista_puede_filtrarse_por_una_sucursal_asignada(): void
    {
        $otraSucursal = Sucursal::query()->create([
            'scl_nombre' => 'Sucursal Móvil Norte',
            'scl_clave' => 'MOV-NORTE',
            'scl_estatus' => 'activo',
        ]);
        $otroAlmacen = Almacen::query()->create([
            'alm_scl_id' => $otraSucursal->scl_id,
            'alm_tal_id' => TipoAlmacen::query()->where('tal_clave', 'principal')->value('tal_id'),
            'alm_nombre' => 'Almacén Móvil Norte',
            'alm_clave' => 'ALM-MOV-NORTE',
            'alm_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $this->usuario->usr_id,
            'usc_scl_id' => $otraSucursal->scl_id,
            'usc_es_predeterminada' => false,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);
        $this->assignProduct($this->skuPieza, $otroAlmacen);

        foreach ([
            [$this->sucursal->scl_id, $this->almacenPrincipal->alm_id],
            [$otraSucursal->scl_id, $otroAlmacen->alm_id],
        ] as [$sucursalId, $almacenId]) {
            $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
                'request_id' => (string) Str::uuid(),
                'branch_id' => $sucursalId,
                'lines' => [[
                    'sku_id' => $this->skuPieza->psk_id,
                    'warehouse_id' => $almacenId,
                    'quantity' => 1,
                ]],
            ])->assertCreated();
        }

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders?status=all&branch_id=' . $otraSucursal->scl_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.warehouse', 'Almacén Móvil Norte');

        $noAsignada = Sucursal::query()->create([
            'scl_nombre' => 'Sucursal no asignada móvil',
            'scl_clave' => 'MOV-NO-ASIGNADA',
            'scl_estatus' => 'activo',
        ]);
        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders?branch_id=' . $noAsignada->scl_id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_token_sin_habilidad_de_pedidos_no_puede_usar_el_modulo(): void
    {
        $tokenSinHabilidad = $this->usuario->createToken('sin-habilidad', ['mobile:profile'])->plainTextToken;

        $this->withToken($tokenSinHabilidad)
            ->getJson('/api/v1/mobile/floor-orders')
            ->assertForbidden();
    }

    public function test_rechaza_cantidad_decimal_para_producto_por_pieza(): void
    {
        $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1.5,
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('partidas');

        $this->assertSame(0, PedidoPiso::query()->count());
    }

    public function test_vendedor_puede_cancelar_su_pedido_mientras_esta_pendiente(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ])->assertCreated();
        $pedidoId = (int) $created->json('data.orders.0.id');

        $this->withToken($this->token)
            ->deleteJson('/api/v1/mobile/floor-orders/' . $pedidoId)
            ->assertOk()
            ->assertJsonPath('message', 'Pedido cancelado correctamente.');

        $this->assertDatabaseHas('tbl_pedidos_piso_pdp', [
            'pdp_id' => $pedidoId,
            'pdp_estatus' => 'cancelado',
            'pdp_deleted' => true,
        ]);
        $this->assertDatabaseMissing('tbl_pedido_piso_detalle_ppd', [
            'ppd_pdp_id' => $pedidoId,
            'ppd_deleted' => false,
        ]);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders?status=cancelled')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pedidoId)
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.0.item_count', 1);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders/' . $pedidoId)
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonCount(1, 'data.lines')
            ->assertJsonPath('data.lines.0.sku_id', $this->skuPieza->psk_id)
            ->assertJsonPath('data.lines.0.quantity', 1);
    }

    public function test_vendedor_puede_actualizar_su_pedido_pendiente_sin_cambiar_folio(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'notes' => 'Nota inicial',
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ])->assertCreated();
        $pedidoId = (int) $created->json('data.orders.0.id');
        $folio = (string) $created->json('data.orders.0.folio');

        $this->withToken($this->token)->putJson('/api/v1/mobile/floor-orders/' . $pedidoId, [
            'branch_id' => $this->sucursal->scl_id,
            'notes' => 'Pedido corregido desde Android',
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 2,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'discount_quantity' => 2,
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $pedidoId)
            ->assertJsonPath('data.folio', $folio)
            ->assertJsonPath('data.total', 629.82)
            ->assertJsonPath('data.notes', 'Pedido corregido desde Android')
            ->assertJsonPath('data.lines.0.discount_type', 'percentage')
            ->assertJsonPath('data.lines.0.discount_value', 10)
            ->assertJsonPath('data.lines.0.discount_quantity', 2);

        $this->assertSame(1, DB::table('tbl_pedido_piso_detalle_ppd')
            ->where('ppd_pdp_id', $pedidoId)
            ->where('ppd_deleted', false)
            ->count());
        $this->assertSame(1, DB::table('tbl_pedido_piso_detalle_ppd')
            ->where('ppd_pdp_id', $pedidoId)
            ->where('ppd_deleted', true)
            ->count());
    }

    public function test_no_actualiza_un_pedido_que_ya_fue_cobrado(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ])->assertCreated();
        $pedidoId = (int) $created->json('data.orders.0.id');
        PedidoPiso::query()->where('pdp_id', $pedidoId)->update(['pdp_estatus' => 'cobrado']);

        $this->withToken($this->token)->putJson('/api/v1/mobile/floor-orders/' . $pedidoId, [
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 2,
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pedido');
    }

    public function test_lista_refleja_como_pagado_un_pedido_cobrado_en_caja(): void
    {
        $created = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ])->assertCreated();
        $pedidoId = (int) $created->json('data.orders.0.id');

        PedidoPiso::query()->where('pdp_id', $pedidoId)->update(['pdp_estatus' => 'cobrado']);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pedidoId)
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.item_count', 1);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/floor-orders/' . $pedidoId)
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_descuento_parcial_se_envia_como_partida_separada_y_laravel_calcula_el_total(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [
                [
                    'sku_id' => $this->skuPieza->psk_id,
                    'warehouse_id' => $this->almacenPrincipal->alm_id,
                    'quantity' => 3,
                    'discount_type' => 'none',
                    'discount_value' => 0,
                ],
                [
                    'sku_id' => $this->skuPieza->psk_id,
                    'warehouse_id' => $this->almacenPrincipal->alm_id,
                    'quantity' => 2,
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                    'discount_quantity' => 2,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.orders.0.subtotal', 1749.5)
            ->assertJsonPath('data.orders.0.total', 1679.52)
            ->assertJsonCount(2, 'data.orders.0.lines');

        $pedidoId = (int) $response->json('data.orders.0.id');
        $this->assertDatabaseHas('tbl_pedidos_piso_pdp', [
            'pdp_id' => $pedidoId,
            'pdp_subtotal' => 1749.50,
            'pdp_total' => 1679.52,
        ]);
        $this->assertDatabaseHas('tbl_pedido_piso_detalle_ppd', [
            'ppd_pdp_id' => $pedidoId,
            'ppd_cantidad' => 2,
            'ppd_descuento_tipo' => 'porcentaje',
            'ppd_descuento_valor' => 10,
            'ppd_descuento_importe' => 69.98,
            'ppd_total_linea' => 629.82,
        ]);
    }

    public function test_usuario_sin_permiso_web_no_puede_crear_pedidos_aunque_tenga_token_mobile(): void
    {
        $usuario = Usuario::query()->create([
            'usr_usuario' => 'mobile_sin_permiso',
            'usr_nombre' => 'Móvil sin permiso',
            'usr_password' => Hash::make('Password123!'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $usuario->usr_id,
            'usc_scl_id' => $this->sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);
        $token = $usuario->createToken('mobile-sin-permiso', ['mobile:orders'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/mobile/floor-orders', [
            'request_id' => (string) Str::uuid(),
            'branch_id' => $this->sucursal->scl_id,
            'lines' => [[
                'sku_id' => $this->skuPieza->psk_id,
                'warehouse_id' => $this->almacenPrincipal->alm_id,
                'quantity' => 1,
            ]],
        ])->assertForbidden();
    }

    private function assignProduct(ProductoSku $sku, Almacen $almacen): void
    {
        DB::table('tbl_producto_almacenes_pra')->insert([
            'pra_prd_id' => Producto::query()->where('prd_id', $sku->psk_prd_id)->value('prd_id'),
            'pra_alm_id' => $almacen->alm_id,
            'pra_deleted' => false,
            'pra_created_at' => now(),
            'pra_updated_at' => now(),
        ]);
    }
}
