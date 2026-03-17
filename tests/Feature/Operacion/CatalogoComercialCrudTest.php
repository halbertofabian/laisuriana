<?php

namespace Tests\Feature\Operacion;

use App\Models\Atributo;
use App\Models\Categoria;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\ProveedorContacto;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\UnidadMedida;
use App\Models\Usuario;
use App\Models\ValorAtributo;
use Database\Seeders\SeguridadBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogoComercialCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SeguridadBaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);
    }

    public function test_crud_marca_con_borrado_logico_y_bitacora(): void
    {
        $store = $this->postJson(route('operacion.catalogo_comercial.catalogos.store', ['tipo' => 'marcas']), [
            'nombre' => 'Marca QA',
            'clave' => 'MRC_QA',
            'estatus' => 'activo',
        ]);

        $store->assertOk();
        $marcaId = (int) data_get($store->json(), 'data.id');

        $this->assertDatabaseHas('tbl_marcas_mrc', [
            'mrc_id' => $marcaId,
            'mrc_nombre' => 'Marca QA',
            'mrc_estatus' => 'activo',
            'mrc_deleted' => false,
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.marcas.crear',
            'bac_entidad' => 'tbl_marcas_mrc',
            'bac_entidad_id' => (string) $marcaId,
        ]);

        $update = $this->putJson(route('operacion.catalogo_comercial.catalogos.update', ['tipo' => 'marcas', 'id' => $marcaId]), [
            'nombre' => 'Marca QA Actualizada',
            'clave' => 'MRC_QA',
            'estatus' => 'activo',
        ]);

        $update->assertOk();

        $this->assertDatabaseHas('tbl_marcas_mrc', [
            'mrc_id' => $marcaId,
            'mrc_nombre' => 'Marca QA Actualizada',
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.marcas.editar',
            'bac_entidad_id' => (string) $marcaId,
        ]);

        $estatus = $this->patchJson(route('operacion.catalogo_comercial.catalogos.estatus', ['tipo' => 'marcas', 'id' => $marcaId]), [
            'estatus' => 'inactivo',
        ]);

        $estatus->assertOk();

        $this->assertDatabaseHas('tbl_marcas_mrc', [
            'mrc_id' => $marcaId,
            'mrc_estatus' => 'inactivo',
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.marcas.inactivar',
            'bac_entidad_id' => (string) $marcaId,
        ]);

        $delete = $this->deleteJson(route('operacion.catalogo_comercial.catalogos.destroy', ['tipo' => 'marcas', 'id' => $marcaId]));
        $delete->assertOk();

        $this->assertDatabaseHas('tbl_marcas_mrc', [
            'mrc_id' => $marcaId,
            'mrc_deleted' => true,
            'mrc_estatus' => 'inactivo',
        ]);

        $this->assertNotNull(DB::table('tbl_marcas_mrc')->where('mrc_id', $marcaId)->value('mrc_deleted_at'));

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.marcas.eliminar',
            'bac_entidad_id' => (string) $marcaId,
        ]);
    }

    public function test_no_permite_inactivar_marca_con_productos_relacionados(): void
    {
        $marca = Marca::query()->create([
            'mrc_nombre' => 'Marca Bloqueada',
            'mrc_clave' => 'MRC_BLOQ',
            'mrc_estatus' => 'activo',
        ]);

        $linea = Linea::query()->create([
            'lna_nombre' => 'Línea Base',
            'lna_clave' => 'LNA_BASE',
            'lna_estatus' => 'activo',
        ]);

        $categoria = Categoria::query()->create([
            'ctg_nombre' => 'Categoría Base',
            'ctg_clave' => 'CTG_BASE',
            'ctg_estatus' => 'activo',
        ]);

        $unidad = UnidadMedida::query()->create([
            'umd_nombre' => 'Pieza',
            'umd_codigo' => 'PZA',
            'umd_clave' => 'UMD_PZA',
            'umd_estatus' => 'activo',
        ]);

        Producto::query()->create([
            'prd_codigo' => 'PRD-0001',
            'prd_nombre' => 'Producto Dependiente',
            'prd_mrc_id' => $marca->mrc_id,
            'prd_lna_id' => $linea->lna_id,
            'prd_ctg_id' => $categoria->ctg_id,
            'prd_umd_id' => $unidad->umd_id,
            'prd_estatus' => 'activo',
        ]);

        $response = $this->patchJson(route('operacion.catalogo_comercial.catalogos.estatus', ['tipo' => 'marcas', 'id' => $marca->mrc_id]), [
            'estatus' => 'inactivo',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['mrc_id']);
    }

    public function test_crud_proveedor_con_contactos_y_borrado_logico(): void
    {
        $store = $this->postJson(route('operacion.catalogo_comercial.proveedores.store'), [
            'prv_nombre_empresa' => 'Proveedor QA',
            'prv_nombre_asesor_ventas' => 'Laura Ventas',
            'prv_categoria' => 'Textiles',
            'prv_razon_social' => 'Proveedor QA SA de CV',
            'prv_rfc' => 'XAXX010101000',
            'prv_correo' => 'contacto@proveedor-qa.mx',
            'prv_condiciones_pago' => 'Crédito a 30 días',
            'prv_tiempo_respuesta' => '24 horas',
            'prv_estatus' => 'activo',
            'numeros_contacto' => ['5512345678', '5587654321'],
        ]);

        $store->assertOk();
        $proveedorId = (int) data_get($store->json(), 'data.prv_id');

        $this->assertDatabaseHas('tbl_proveedores_prv', [
            'prv_id' => $proveedorId,
            'prv_nombre_empresa' => 'Proveedor QA',
            'prv_rfc' => 'XAXX010101000',
            'prv_deleted' => false,
        ]);

        $this->assertSame(
            2,
            ProveedorContacto::query()->where('prc_prv_id', $proveedorId)->where('prc_deleted', false)->count()
        );

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.proveedor.crear',
            'bac_entidad_id' => (string) $proveedorId,
        ]);

        $update = $this->putJson(route('operacion.catalogo_comercial.proveedores.update', ['proveedor' => $proveedorId]), [
            'prv_nombre_empresa' => 'Proveedor QA Actualizado',
            'prv_nombre_asesor_ventas' => 'Laura Ventas',
            'prv_categoria' => 'Confección',
            'prv_razon_social' => 'Proveedor QA SA de CV',
            'prv_rfc' => 'XAXX010101000',
            'prv_correo' => 'ventas@proveedor-qa.mx',
            'prv_condiciones_pago' => 'Crédito a 15 días',
            'prv_tiempo_respuesta' => '48 horas',
            'prv_estatus' => 'activo',
            'numeros_contacto' => ['5587654321', '5599990000'],
        ]);

        $update->assertOk();

        $this->assertDatabaseHas('tbl_proveedores_prv', [
            'prv_id' => $proveedorId,
            'prv_nombre_empresa' => 'Proveedor QA Actualizado',
            'prv_categoria' => 'Confección',
        ]);

        $this->assertDatabaseHas('tbl_proveedor_contactos_prc', [
            'prc_prv_id' => $proveedorId,
            'prc_numero' => '5512345678',
            'prc_deleted' => true,
            'prc_estatus' => 'inactivo',
        ]);

        $this->assertDatabaseHas('tbl_proveedor_contactos_prc', [
            'prc_prv_id' => $proveedorId,
            'prc_numero' => '5599990000',
            'prc_deleted' => false,
            'prc_estatus' => 'activo',
        ]);

        $this->patchJson(route('operacion.catalogo_comercial.proveedores.estatus', ['proveedor' => $proveedorId]), [
            'prv_estatus' => 'inactivo',
        ])->assertOk();

        $this->assertDatabaseHas('tbl_proveedores_prv', [
            'prv_id' => $proveedorId,
            'prv_estatus' => 'inactivo',
        ]);

        $this->deleteJson(route('operacion.catalogo_comercial.proveedores.destroy', ['proveedor' => $proveedorId]))
            ->assertOk();

        $this->assertDatabaseHas('tbl_proveedores_prv', [
            'prv_id' => $proveedorId,
            'prv_deleted' => true,
            'prv_estatus' => 'inactivo',
        ]);

        $this->assertSame(
            0,
            ProveedorContacto::query()->where('prc_prv_id', $proveedorId)->where('prc_deleted', false)->count()
        );

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.proveedor.editar',
            'bac_entidad_id' => (string) $proveedorId,
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.proveedor.inactivar',
            'bac_entidad_id' => (string) $proveedorId,
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.proveedor.eliminar',
            'bac_entidad_id' => (string) $proveedorId,
        ]);
    }

    public function test_proveedor_solo_requiere_nombre_empresa(): void
    {
        $store = $this->postJson(route('operacion.catalogo_comercial.proveedores.store'), [
            'prv_nombre_empresa' => 'Proveedor Mínimo',
        ]);

        $store->assertOk();
        $proveedorId = (int) data_get($store->json(), 'data.prv_id');

        $this->assertDatabaseHas('tbl_proveedores_prv', [
            'prv_id' => $proveedorId,
            'prv_nombre_empresa' => 'Proveedor Mínimo',
            'prv_estatus' => 'activo',
            'prv_deleted' => false,
        ]);

        $this->assertSame(
            0,
            ProveedorContacto::query()->where('prc_prv_id', $proveedorId)->where('prc_deleted', false)->count()
        );
    }

    public function test_producto_crud_sincroniza_atributos_corridas_y_borrado_logico(): void
    {
        $ctx = $this->crearContextoProductoVariable();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Tela Gabardina',
            'prd_descripcion' => 'Prueba de producto variable',
            'prd_precio_base' => 189.90,
            'prd_stock_minimo' => 5,
            'prd_stock_maximo' => 25,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['color']->atr_id, $ctx['atributos']['material']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id],
                $ctx['atributos']['material']->atr_id => [$ctx['valores']['material_algodon']->vat_id, $ctx['valores']['material_poliester']->vat_id],
            ],
        ]);

        $store->assertOk();
        $productoId = (int) data_get($store->json(), 'data.prd_id');

        $this->assertDatabaseHas('tbl_producto_atributos_pat', [
            'pat_prd_id' => $productoId,
            'pat_atr_id' => $ctx['atributos']['color']->atr_id,
            'pat_deleted' => false,
        ]);

        $this->assertDatabaseHas('tbl_producto_atributos_pat', [
            'pat_prd_id' => $productoId,
            'pat_atr_id' => $ctx['atributos']['material']->atr_id,
            'pat_deleted' => false,
        ]);

        $this->assertSame(2, ProductoSku::query()->where('psk_prd_id', $productoId)->where('psk_deleted', false)->count());

        $update = $this->putJson(route('operacion.catalogo_comercial.productos.update', ['producto' => $productoId]), [
            'prd_nombre' => 'Tela Gabardina Premium',
            'prd_descripcion' => 'Prueba editada',
            'prd_precio_base' => 189.90,
            'prd_stock_minimo' => 5,
            'prd_stock_maximo' => 25,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id],
            ],
        ]);

        $update->assertOk();

        $this->assertDatabaseHas('tbl_producto_atributos_pat', [
            'pat_prd_id' => $productoId,
            'pat_atr_id' => $ctx['atributos']['material']->atr_id,
            'pat_deleted' => true,
            'pat_estatus' => 'inactivo',
        ]);

        $this->assertSame(1, ProductoSku::query()->where('psk_prd_id', $productoId)->where('psk_deleted', false)->count());
        $this->assertSame(3, ProductoSku::query()->withDeleted()->where('psk_prd_id', $productoId)->count());
        $this->assertSame(2, ProductoSku::query()->withDeleted()->where('psk_prd_id', $productoId)->where('psk_deleted', true)->count());

        $this->patchJson(route('operacion.catalogo_comercial.productos.estatus', ['producto' => $productoId]), [
            'prd_estatus' => 'inactivo',
        ])->assertOk();

        $this->assertDatabaseHas('tbl_producto_skus_psk', [
            'psk_prd_id' => $productoId,
            'psk_deleted' => false,
            'psk_estatus' => 'inactivo',
        ]);

        $this->deleteJson(route('operacion.catalogo_comercial.productos.destroy', ['producto' => $productoId]))
            ->assertOk();

        $this->assertDatabaseHas('tbl_productos_prd', [
            'prd_id' => $productoId,
            'prd_deleted' => true,
            'prd_estatus' => 'inactivo',
        ]);

        $this->assertSame(3, ProductoSku::query()->withDeleted()->where('psk_prd_id', $productoId)->where('psk_deleted', true)->count());

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.producto.crear',
            'bac_entidad_id' => (string) $productoId,
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.producto.editar',
            'bac_entidad_id' => (string) $productoId,
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.producto.eliminar',
            'bac_entidad_id' => (string) $productoId,
        ]);
    }

    public function test_eliminar_sku_automatico_marca_borrado_logico_y_desactiva_pivote(): void
    {
        $ctx = $this->crearContextoProductoVariable();

        $store = $this->postJson(route('operacion.catalogo_comercial.productos.store'), [
            'prd_nombre' => 'Producto con una corrida',
            'prd_descripcion' => 'Prueba para eliminar SKU automático',
            'prd_precio_base' => 99.90,
            'prd_stock_minimo' => 1,
            'prd_stock_maximo' => 10,
            'prd_mrc_id' => $ctx['marca']->mrc_id,
            'prd_lna_id' => $ctx['linea']->lna_id,
            'prd_ctg_id' => $ctx['categoria']->ctg_id,
            'prd_umd_id' => $ctx['unidad']->umd_id,
            'prd_tipo' => 'variable',
            'prd_estatus' => 'activo',
            'atributo_ids' => [$ctx['atributos']['color']->atr_id],
            'atributo_valores' => [
                $ctx['atributos']['color']->atr_id => [$ctx['valores']['color_azul']->vat_id],
            ],
        ]);

        $store->assertOk();
        $productoId = (int) data_get($store->json(), 'data.prd_id');

        $sku = ProductoSku::query()
            ->where('psk_prd_id', $productoId)
            ->where('psk_deleted', false)
            ->whereNull('psk_deleted_at')
            ->firstOrFail();

        $this->deleteJson(route('operacion.catalogo_comercial.skus.destroy', ['sku' => $sku->psk_id]))
            ->assertOk();

        $this->assertDatabaseHas('tbl_producto_skus_psk', [
            'psk_id' => $sku->psk_id,
            'psk_deleted' => true,
            'psk_estatus' => 'inactivo',
        ]);

        $this->assertDatabaseHas('tbl_sku_valores_atributo_sva', [
            'sva_psk_id' => $sku->psk_id,
            'sva_deleted' => true,
            'sva_estatus' => 'inactivo',
        ]);

        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', [
            'bac_accion' => 'catalogo_comercial.sku.eliminar',
            'bac_entidad_id' => (string) $sku->psk_id,
        ]);
    }

    private function crearContextoProductoVariable(): array
    {
        $marca = Marca::query()->create(['mrc_nombre' => 'Marca X', 'mrc_clave' => 'MRC_X', 'mrc_estatus' => 'activo']);
        $linea = Linea::query()->create(['lna_nombre' => 'Línea X', 'lna_clave' => 'LNA_X', 'lna_estatus' => 'activo']);
        $categoria = Categoria::query()->create(['ctg_nombre' => 'Ropa X', 'ctg_clave' => 'CTG_X', 'ctg_estatus' => 'activo']);
        $unidad = UnidadMedida::query()->create(['umd_nombre' => 'Pieza X', 'umd_codigo' => 'PZX', 'umd_clave' => 'UMD_X', 'umd_estatus' => 'activo']);

        $atributoColor = Atributo::query()->create(['atr_nombre' => 'Color X', 'atr_clave' => 'ATR_COLOR_X', 'atr_estatus' => 'activo']);
        $atributoMaterial = Atributo::query()->create(['atr_nombre' => 'Material X', 'atr_clave' => 'ATR_MAT_X', 'atr_estatus' => 'activo']);

        $colorAzul = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoColor->atr_id,
            'vat_valor' => 'Azul',
            'vat_clave' => 'VAT_AZUL_X',
            'vat_estatus' => 'activo',
        ]);

        $materialAlgodon = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoMaterial->atr_id,
            'vat_valor' => 'Algodón',
            'vat_clave' => 'VAT_ALGODON_X',
            'vat_estatus' => 'activo',
        ]);

        $materialPoliester = ValorAtributo::query()->create([
            'vat_atr_id' => $atributoMaterial->atr_id,
            'vat_valor' => 'Poliéster',
            'vat_clave' => 'VAT_POLIESTER_X',
            'vat_estatus' => 'activo',
        ]);

        return [
            'marca' => $marca,
            'linea' => $linea,
            'categoria' => $categoria,
            'unidad' => $unidad,
            'atributos' => [
                'color' => $atributoColor,
                'material' => $atributoMaterial,
            ],
            'valores' => [
                'color_azul' => $colorAzul,
                'material_algodon' => $materialAlgodon,
                'material_poliester' => $materialPoliester,
            ],
        ];
    }
}
