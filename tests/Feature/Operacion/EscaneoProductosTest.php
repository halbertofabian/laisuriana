<?php

namespace Tests\Feature\Operacion;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EscaneoProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_modulo_escaneo_requiere_permiso(): void
    {
        $this->seed(DatabaseSeeder::class);

        $usuarioSinPermisos = Usuario::query()->create([
            'usr_nombre' => 'Usuario sin permiso escaneo',
            'usr_usuario' => 'usuario_sin_escaneo',
            'usr_email' => 'sin.escaneo@lasuriana.local',
            'usr_password' => Hash::make('Password123!'),
            'usr_estatus' => 'activo',
        ]);

        $this->actingAs($usuarioSinPermisos);

        $this->get(route('operacion.escaneo_productos.index'))
            ->assertForbidden();
    }

    public function test_admin_puede_consultar_producto_por_barcode(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $response = $this->getJson(route('operacion.escaneo_productos.buscar', [
            'q' => '7509000100018',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.psk_codigo', 'SKU-POLO-CH-AZM');
        $response->assertJsonPath('data.producto.prd_codigo', 'PRD-POLO-H-001');
        $response->assertJsonStructure([
            'data' => [
                'psk_id',
                'psk_codigo',
                'producto' => ['prd_id', 'prd_codigo', 'prd_nombre'],
            ],
        ]);
    }
}
