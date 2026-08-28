<?php

namespace Tests\Feature\Api;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileOrderCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Sucursal $sucursal;
    private Almacen $almacen;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->usuario = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->sucursal = Sucursal::query()->where('scl_clave', 'MATRIZ')->firstOrFail();
        $this->almacen = Almacen::query()->where('alm_scl_id', $this->sucursal->scl_id)->firstOrFail();
        $this->token = $this->usuario->createToken('mobile-catalog-test', ['mobile:orders'])->plainTextToken;
    }

    public function test_catalogo_movil_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/mobile/order-context')->assertUnauthorized();
        $this->getJson('/api/v1/mobile/products?q=polo&branch_id=' . $this->sucursal->scl_id)->assertUnauthorized();
        $this->getJson('/api/v1/mobile/clients?q=ana')->assertUnauthorized();
    }

    public function test_contexto_solo_devuelve_sucursales_activas_asignadas(): void
    {
        Sucursal::query()->create([
            'scl_nombre' => 'Sucursal no asignada',
            'scl_clave' => 'NO-ASIGNADA',
            'scl_estatus' => 'activo',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/order-context')
            ->assertOk()
            ->assertJsonPath('data.sucursal_predeterminada_id', $this->sucursal->scl_id)
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonPath('data.sucursales.0.id', $this->sucursal->scl_id)
            ->assertJsonPath('data.sucursales.0.name', $this->sucursal->scl_nombre);
    }

    public function test_busqueda_devuelve_producto_con_precio_unidad_y_almacen_de_la_sucursal(): void
    {
        $producto = Producto::query()->where('prd_codigo', 'PRD-POLO-H-001')->firstOrFail();
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $this->assignProduct($producto, $this->almacen);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/products?q=7509000100018&branch_id=' . $this->sucursal->scl_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sku->psk_id)
            ->assertJsonPath('data.0.sku', 'SKU-POLO-CH-AZM')
            ->assertJsonPath('data.0.price', 349.9)
            ->assertJsonPath('data.0.unit.code', 'PZA')
            ->assertJsonPath('data.0.allows_decimal', false)
            ->assertJsonPath('data.0.requires_warehouse_selection', false)
            ->assertJsonPath('data.0.warehouse_id', $this->almacen->alm_id)
            ->assertJsonPath('data.0.warehouses.0.name', $this->almacen->alm_nombre);
    }

    public function test_producto_con_dos_almacenes_exige_seleccion(): void
    {
        $producto = Producto::query()->where('prd_codigo', 'PRD-TELA-GAB-001')->firstOrFail();
        $sku = ProductoSku::query()->where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();
        $otroAlmacen = Almacen::query()->create([
            'alm_scl_id' => $this->sucursal->scl_id,
            'alm_tal_id' => TipoAlmacen::query()->where('tal_clave', 'principal')->value('tal_id'),
            'alm_nombre' => 'Almacén Secundario',
            'alm_clave' => 'ALM-SECUNDARIO',
            'alm_estatus' => 'activo',
        ]);
        $this->assignProduct($producto, $this->almacen);
        $this->assignProduct($producto, $otroAlmacen);

        $this->withToken($this->token)
            ->getJson("/api/v1/mobile/products/{$sku->psk_id}/warehouses?branch_id={$this->sucursal->scl_id}")
            ->assertOk()
            ->assertJsonPath('data.requires_selection', true)
            ->assertJsonCount(2, 'data.warehouses');
    }

    public function test_no_permite_consultar_una_sucursal_no_asignada(): void
    {
        $otraSucursal = Sucursal::query()->create([
            'scl_nombre' => 'Sucursal Norte',
            'scl_clave' => 'NORTE',
            'scl_estatus' => 'activo',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/products?q=polo&branch_id=' . $otraSucursal->scl_id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_busqueda_de_clientes_incluye_razon_social_y_publico_general_es_responsabilidad_de_la_app(): void
    {
        $cliente = Cliente::query()->create([
            'cli_nombre' => 'María',
            'cli_apellido_paterno' => 'López',
            'cli_razon_social' => 'Constructora Horizonte SA de CV',
            'cli_rfc' => 'CHO260101AA1',
            'cli_estatus' => 'activo',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/v1/mobile/clients?q=Horizonte')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cliente->cli_id)
            ->assertJsonPath('data.0.name', 'Constructora Horizonte SA de CV')
            ->assertJsonPath('data.0.detail', 'RFC CHO260101AA1');
    }

    private function assignProduct(Producto $producto, Almacen $almacen): void
    {
        DB::table('tbl_producto_almacenes_pra')->insert([
            'pra_prd_id' => $producto->prd_id,
            'pra_alm_id' => $almacen->alm_id,
            'pra_deleted' => false,
            'pra_created_at' => now(),
            'pra_updated_at' => now(),
        ]);
    }
}
