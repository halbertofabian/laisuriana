<?php

namespace Tests\Feature\Api;

use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_busqueda_sugiere_usuarios_activos_desde_dos_caracteres(): void
    {
        $this->crearUsuario('daniela', 'Daniela Martínez');
        $this->crearUsuario('dario', 'Darío López', 'inactivo');

        $this->getJson('/api/v1/mobile/auth/usuarios?q=d')
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $this->getJson('/api/v1/mobile/auth/usuarios?q=dan')
            ->assertOk()
            ->assertJsonPath('data.0.usuario', 'daniela')
            ->assertJsonPath('data.0.nombre', 'Daniela Martínez')
            ->assertJsonCount(1, 'data');
    }

    public function test_login_entrega_token_y_contexto_del_vendedor(): void
    {
        $usuario = $this->crearUsuario('daniela', 'Daniela Martínez');
        $sucursal = Sucursal::query()->create([
            'scl_nombre' => 'Sucursal Centro',
            'scl_clave' => 'CENTRO',
            'scl_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $usuario->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);

        $response = $this->postJson('/api/v1/mobile/auth/login', [
            'usuario' => 'daniela',
            'password' => 'Secret123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.usuario.usuario', 'daniela')
            ->assertJsonPath('data.usuario.nombre', 'Daniela Martínez')
            ->assertJsonPath('data.usuario.sucursal_predeterminada_id', $sucursal->scl_id)
            ->assertJsonPath('data.usuario.sucursales.0.nombre', 'Sucursal Centro');

        $token = (string) $response->json('data.token');
        $this->assertNotSame('', $token);

        $this->withToken($token)
            ->getJson('/api/v1/mobile/auth/sesion')
            ->assertOk()
            ->assertJsonPath('data.usuario.usuario', 'daniela');
    }

    public function test_login_incorrecto_no_crea_token(): void
    {
        $usuario = $this->crearUsuario('daniela', 'Daniela Martínez');

        $this->postJson('/api/v1/mobile/auth/login', [
            'usuario' => 'daniela',
            'password' => 'incorrecta',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertCount(0, $usuario->tokens()->get());
    }

    private function crearUsuario(string $usuario, string $nombre, string $estatus = 'activo'): Usuario
    {
        return Usuario::query()->create([
            'usr_usuario' => $usuario,
            'usr_nombre' => $nombre,
            'usr_password' => Hash::make('Secret123!'),
            'usr_estatus' => $estatus,
        ]);
    }
}
