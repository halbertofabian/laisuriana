<?php

namespace Tests\Feature\Operacion;

use App\Models\Cliente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosDatosFacturacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Usuario::where('usr_usuario', 'admin')->firstOrFail());
    }

    private function datos(array $extra = []): array
    {
        return array_replace([
            'cli_razon_social' => 'CLIENTE DE PRUEBA FACTURACION',
            'cli_rfc' => ' xaxa010101ab1 ',
            'cli_regimen_fiscal' => '621',
            'cli_uso_cfdi' => 'G03 · Gastos en general',
            'cli_cp' => '03000',
            'cli_forma_pago' => 'Crédito',
        ], $extra);
    }

    public function test_crea_cliente_con_datos_minimos_y_lo_encuentra_en_el_pos(): void
    {
        $response = $this->postJson(route('pos.datos_facturacion.store'), $this->datos());
        $response->assertCreated()->assertJsonPath('data.nombre', 'CLIENTE DE PRUEBA FACTURACION');
        $id = $response->json('data.cli_id');
        $this->assertDatabaseHas('tbl_clientes_cli', [
            'cli_id' => $id, 'cli_rfc' => 'XAXA010101AB1', 'cli_regimen_fiscal' => '621',
            'cli_uso_cfdi' => 'G03 · Gastos en general', 'cli_cp' => '03000', 'cli_forma_pago' => 'Crédito',
            'cli_estatus' => 'activo', 'cli_email' => null, 'cli_telefono' => null,
        ]);
        $this->getJson(route('pos.clientes.buscar', ['q' => 'XAXA010101AB1']))
            ->assertOk()->assertJsonPath('data.0.cli_id', $id);
    }

    public function test_conserva_razon_social_larga_contacto_y_busqueda(): void
    {
        $nombre = str_repeat('EMPRESA ', 18).'FINAL';
        $response = $this->postJson(route('pos.datos_facturacion.store'), $this->datos([
            'cli_razon_social' => $nombre, 'cli_email' => 'facturas@example.test', 'cli_telefono' => '9631234567',
        ]))->assertCreated();
        $cliente = Cliente::findOrFail($response->json('data.cli_id'));
        $this->assertSame($nombre, $cliente->cli_razon_social);
        $this->assertSame('facturas@example.test', $cliente->cli_email);
        $this->assertSame('9631234567', $cliente->cli_telefono);
        $this->getJson(route('pos.clientes.buscar', ['q' => 'FINAL']))
            ->assertOk()->assertJsonPath('data.0.nombre', $nombre);
    }

    public function test_rechaza_campos_invalidos_y_rfc_duplicado_sin_crear_otro_cliente(): void
    {
        $this->postJson(route('pos.datos_facturacion.store'), $this->datos())->assertCreated();
        $cantidad = Cliente::count();
        $this->postJson(route('pos.datos_facturacion.store'), $this->datos())
            ->assertUnprocessable()->assertJsonValidationErrors('cli_rfc');
        $this->postJson(route('pos.datos_facturacion.store'), $this->datos([
            'cli_rfc' => 'incorrecto', 'cli_cp' => '1234', 'cli_regimen_fiscal' => '999', 'cli_email' => 'sin-correo',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['cli_rfc', 'cli_cp', 'cli_regimen_fiscal', 'cli_email']);
        $this->assertSame($cantidad, Cliente::count());
    }

    public function test_solo_acepta_campos_del_alta_rapida_y_respeta_permiso(): void
    {
        $response = $this->postJson(route('pos.datos_facturacion.store'), $this->datos([
            'cli_descuento_default' => 90, 'cli_estatus' => 'inactivo',
        ]))->assertCreated();
        $cliente = Cliente::findOrFail($response->json('data.cli_id'));
        $this->assertNull($cliente->cli_descuento_default);
        $this->assertSame('activo', $cliente->cli_estatus);

        $sinPermiso = Usuario::create([
            'usr_nombre' => 'Sin permiso', 'usr_usuario' => 'sin-permiso-fiscal',
            'usr_password' => bcrypt('test-password'), 'usr_estatus' => 'activo',
        ]);
        $this->actingAs($sinPermiso)->postJson(route('pos.datos_facturacion.store'), $this->datos())->assertForbidden();
    }

    public function test_pos_muestra_boton_nuevo_y_conserva_modulo_clientes(): void
    {
        $this->get(route('pos.index'))->assertOk()
            ->assertSee('Datos de facturación')
            ->assertSee('facturacion-regimen')
            ->assertSee('626 · Régimen Simplificado de Confianza (RESICO)')
            ->assertSee('form-cliente');
    }
}
