<?php

namespace Tests\Feature\Operacion;

use App\Models\Atributo;
use App\Models\Categoria;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\UnidadMedida;
use App\Models\Usuario;
use App\Models\ValorAtributo;
use Database\Seeders\SeguridadBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoComercialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SeguridadBaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);
    }

    public function test_producto_variable_genera_corridas_y_skus_automaticos(): void
    {
        $ctx = $this->crearContextoBase();

        $response = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Playera Polo Hombre',
            'prd_descripcion' => 'Producto variable para corridas automáticas.',
            'prd_precio_base' => 329.90,
            'prd_stock_minimo' => 2,
            'prd_stock_maximo' => 15,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['talla']->atr_id, $ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['talla']->atr_id => [$ctx['valores']['talla_ch']->vat_id, $ctx['valores']['talla_m']->vat_id],
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id, $ctx['valores']['color_negro']->vat_id],
            ],
        ]);

        $response->assertOk()->assertJsonFragment(['message' => 'Producto creado correctamente.']);

        $producto = Producto::query()->firstOrFail();
        $skus = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->with('valoresAtributo:vat_id,vat_valor')
            ->orderBy('psk_codigo')
            ->get();

        $this->assertSame('variable', $producto->prd_tipo);
        $this->assertNotEmpty($producto->prd_codigo);
        $this->assertCount(4, $skus);
        $this->assertCount(4, $skus->pluck('psk_codigo')->unique());
        $this->assertTrue($skus->every(fn (ProductoSku $sku) => str_starts_with($sku->psk_codigo, $producto->prd_codigo . '-')));
        $this->assertTrue($skus->every(fn (ProductoSku $sku) => (float) $sku->psk_precio === 329.90));
        $this->assertTrue($skus->every(fn (ProductoSku $sku) => $sku->psk_stock_minimo === 2));
        $this->assertTrue($skus->every(fn (ProductoSku $sku) => $sku->psk_stock_maximo === 15));
        $this->assertTrue($skus->every(fn (ProductoSku $sku) => $sku->valoresAtributo->count() === 2));
    }

    public function test_producto_simple_genera_un_solo_sku_estandar(): void
    {
        $ctx = $this->crearContextoBase();

        $response = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Cinturón Clásico',
            'prd_descripcion' => 'Producto simple sin corridas.',
            'prd_precio_base' => 199.50,
            'prd_stock_minimo' => 1,
            'prd_stock_maximo' => 8,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'simple',
            'prd_estatus' => 'activo',
        ]);

        $response->assertOk();

        $producto = Producto::query()->where('prd_nombre', 'Cinturón Clásico')->firstOrFail();
        $sku = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->firstOrFail();

        $this->assertSame('simple', $producto->prd_tipo);
        $this->assertSame($producto->prd_codigo . '-STD', $sku->psk_codigo);
        $this->assertSame('Cinturón Clásico / Estándar', $sku->psk_nombre);
        $this->assertSame('199.50', $sku->psk_precio);
        $this->assertSame(1, $sku->psk_stock_minimo);
        $this->assertSame(8, $sku->psk_stock_maximo);
        $this->assertCount(0, $sku->valoresAtributo);
    }

    public function test_actualizar_producto_variable_regenera_corridas_y_elimina_las_obsoletas(): void
    {
        $ctx = $this->crearContextoBase();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Sudadera Deportiva',
            'prd_descripcion' => 'Producto para probar regeneración.',
            'prd_precio_base' => 449.00,
            'prd_stock_minimo' => 3,
            'prd_stock_maximo' => 20,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['talla']->atr_id, $ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['talla']->atr_id => [$ctx['valores']['talla_m']->vat_id],
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id, $ctx['valores']['color_negro']->vat_id],
            ],
        ]);

        $store->assertOk();
        $productoId = (int) data_get($store->json(), 'data.prd_id');

        $this->assertSame(2, ProductoSku::query()->where('psk_prd_id', $productoId)->where('psk_deleted', false)->count());

        $update = $this->putJson(route('operacion.catalogo_comercial.productos.update', ['producto' => $productoId]), [
            'prd_nombre' => 'Sudadera Deportiva',
            'prd_descripcion' => 'Producto para probar regeneración.',
            'prd_precio_base' => 449.00,
            'prd_stock_minimo' => 3,
            'prd_stock_maximo' => 20,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['talla']->atr_id, $ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['talla']->atr_id => [$ctx['valores']['talla_m']->vat_id, $ctx['valores']['talla_g']->vat_id],
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_negro']->vat_id],
            ],
        ]);

        $update->assertOk();

        $skusActivos = ProductoSku::query()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->count();

        $skusTotales = ProductoSku::query()
            ->withDeleted()
            ->where('psk_prd_id', $productoId)
            ->count();

        $skusEliminados = ProductoSku::query()
            ->withDeleted()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', true)
            ->count();

        $this->assertSame(2, $skusActivos);
        $this->assertSame(3, $skusTotales);
        $this->assertSame(1, $skusEliminados);
    }

    public function test_data_skus_devuelve_las_corridas_generadas_del_producto(): void
    {
        $ctx = $this->crearContextoBase();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Playera catálogo',
            'prd_descripcion' => 'Prueba de listado de SKU.',
            'prd_precio_base' => 250.00,
            'prd_stock_minimo' => 2,
            'prd_stock_maximo' => 12,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['talla']->atr_id, $ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['talla']->atr_id => [$ctx['valores']['talla_ch']->vat_id, $ctx['valores']['talla_m']->vat_id],
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id],
            ],
        ]);

        $store->assertOk();
        $productoId = (int) data_get($store->json(), 'data.prd_id');

        $response = $this->getJson(route('operacion.catalogo_comercial.skus.data', [
            'psk_prd_id' => $productoId,
        ]));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertContains('Color: Azul Marino', $response->json('data.0.combinacion'));
        $this->assertContains('Talla: CH', $response->json('data.0.combinacion'));
    }

    public function test_genera_etiqueta_pdf_programatica_para_sku(): void
    {
        $ctx = $this->crearContextoBase();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'BASESIMPLEQA',
            'prd_descripcion' => 'Prueba de PDF de etiqueta.',
            'prd_precio_base' => 159.90,
            'prd_stock_minimo' => 1,
            'prd_stock_maximo' => 10,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'simple',
            'prd_estatus' => 'activo',
        ]);

        $store->assertOk();

        $producto = Producto::query()->where('prd_nombre', 'BASESIMPLEQA')->firstOrFail();
        $sku = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->firstOrFail();

        $response = $this->get(route('operacion.catalogo_comercial.skus.etiqueta', [
            'sku' => $sku->psk_id,
            'formato' => 'zebra_50x30',
            'copias' => 2,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertSee('%PDF', false);
        $response->assertSee('BASESIMPLEQA', false);
        $response->assertDontSee('/ Est', false);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.sku.generar_etiqueta',
            'bac_entidad' => 'tbl_producto_skus_psk',
            'bac_entidad_id' => (string) $sku->psk_id,
        ]);
    }

    public function test_etiqueta_de_producto_variable_usa_nombre_de_psk(): void
    {
        $ctx = $this->crearContextoBase();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'PRODVARBASEQA',
            'prd_descripcion' => 'Prueba nombre variable para etiqueta.',
            'prd_precio_base' => 210.00,
            'prd_stock_minimo' => 1,
            'prd_stock_maximo' => 12,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['talla']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['talla']->atr_id => [$ctx['valores']['talla_ch']->vat_id],
            ],
        ]);

        $store->assertOk();

        $producto = Producto::query()->where('prd_nombre', 'PRODVARBASEQA')->firstOrFail();
        $sku = ProductoSku::query()
            ->where('psk_prd_id', $producto->prd_id)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->firstOrFail();

        $sku->update(['psk_nombre' => 'NOMBREVARQATEST']);

        $response = $this->get(route('operacion.catalogo_comercial.skus.etiqueta', [
            'sku' => $sku->psk_id,
            'formato' => 'zebra_50x30',
            'copias' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertSee('NOMBREVARQATEST', false);
    }

    private function crearContextoBase(): array
    {
        $marca = Marca::query()->create([
            'mrc_nombre' => 'Marca Prueba',
            'mrc_clave' => 'MRC_PRUEBA',
            'mrc_estatus' => 'activo',
        ]);

        $linea = Linea::query()->create([
            'lna_nombre' => 'Línea Prueba',
            'lna_clave' => 'LNA_PRUEBA',
            'lna_estatus' => 'activo',
        ]);

        $categoria = Categoria::query()->create([
            'ctg_nombre' => 'Ropa',
            'ctg_clave' => 'CTG_ROPA',
            'ctg_estatus' => 'activo',
        ]);

        $unidad = UnidadMedida::query()->create([
            'umd_nombre' => 'Pieza',
            'umd_codigo' => 'PZA',
            'umd_clave' => 'UMD_PZA',
            'umd_estatus' => 'activo',
        ]);

        $atributoTalla = Atributo::query()->create([
            'atr_nombre' => 'Talla',
            'atr_clave' => 'ATR_TALLA',
            'atr_tipo' => 'seleccion',
            'atr_estatus' => 'activo',
        ]);

        $atributoColor = Atributo::query()->create([
            'atr_nombre' => 'Color',
            'atr_clave' => 'ATR_COLOR',
            'atr_tipo' => 'seleccion',
            'atr_estatus' => 'activo',
        ]);

        $tallaCh = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoTalla->atr_id,
            'vat_valor' => 'CH',
            'vat_clave' => 'VAT_TALLA_CH',
            'vat_estatus' => 'activo',
        ]);

        $tallaM = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoTalla->atr_id,
            'vat_valor' => 'M',
            'vat_clave' => 'VAT_TALLA_M',
            'vat_estatus' => 'activo',
        ]);

        $tallaG = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoTalla->atr_id,
            'vat_valor' => 'G',
            'vat_clave' => 'VAT_TALLA_G',
            'vat_estatus' => 'activo',
        ]);

        $colorAzul = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoColor->atr_id,
            'vat_valor' => 'Azul Marino',
            'vat_clave' => 'VAT_COLOR_AZUL',
            'vat_estatus' => 'activo',
        ]);

        $colorNegro = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoColor->atr_id,
            'vat_valor' => 'Negro',
            'vat_clave' => 'VAT_COLOR_NEGRO',
            'vat_estatus' => 'activo',
        ]);

        return [
            'marca' => $marca,
            'linea' => $linea,
            'categoria' => $categoria,
            'unidad' => $unidad,
            'atributos' => [
                'talla' => $atributoTalla,
                'color' => $atributoColor,
            ],
            'valores' => [
                'talla_ch' => $tallaCh,
                'talla_m' => $tallaM,
                'talla_g' => $tallaG,
                'color_azul' => $colorAzul,
                'color_negro' => $colorNegro,
            ],
        ];
    }
}
