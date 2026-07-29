<?php

namespace Tests\Feature\Operacion;

use App\Models\DispositivoImpresora;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use Database\Seeders\SeguridadBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfiguracionImpresoraDispositivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SeguridadBaseSeeder::class);
    }

    public function test_guarda_configuracion_de_impresora_por_dispositivo_y_no_por_usuario(): void
    {
        $deviceId = 'QA-DEVICE-IMPRESORA-001';
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $otroUsuario = Usuario::query()->create([
            'usr_usuario' => 'operador01',
            'usr_password' => Hash::make('12345678'),
            'usr_nombre' => 'Operador QA',
            'usr_email' => 'operador01@example.test',
            'usr_estatus' => 'activo',
        ]);

        $rolAdmin = Rol::query()->where('rol_nombre', 'Administrador')->firstOrFail();

        UsuarioRol::query()->create([
            'url_usr_id' => $otroUsuario->usr_id,
            'url_rol_id' => $rolAdmin->rol_id,
            'url_estatus' => 'activo',
            'url_deleted' => false,
            'url_deleted_at' => null,
        ]);

        $this->actingAs($admin)
            ->withCookie('laisuriana_device_id', $deviceId)
            ->post(route('desktop.operacion.gestion_configuraciones.impresoras.store'), [
                'dip_nombre_dispositivo' => 'MOSTRADOR UNO',
                'dip_tipo_conexion' => 'usb',
                'dip_nombre_impresora' => 'POS-80',
                'dip_agent_url' => 'http://127.0.0.1:17890',
                'dip_controlador' => 'ESC/POS',
            ])
            ->assertRedirect(route('desktop.operacion.gestion_configuraciones.impresoras.index'));

        $this->assertDatabaseHas('tbl_dispositivo_impresoras_dip', [
            'dip_device_uid' => $deviceId,
            'dip_nombre_dispositivo' => 'MOSTRADOR UNO',
            'dip_nombre_impresora' => 'POS-80',
            'dip_tipo_conexion' => 'usb',
            'dip_created_by_usr_id' => $admin->usr_id,
        ]);

        $this->actingAs($otroUsuario)
            ->withCookie('laisuriana_device_id', $deviceId)
            ->post(route('desktop.operacion.gestion_configuraciones.impresoras.store'), [
                'dip_nombre_dispositivo' => 'MOSTRADOR UNO',
                'dip_tipo_conexion' => 'red',
                'dip_nombre_impresora' => 'EPSON TM-T20',
                'dip_host' => '192.168.1.35',
                'dip_puerto' => 9100,
                'dip_controlador' => 'ESC/POS',
            ])
            ->assertRedirect(route('desktop.operacion.gestion_configuraciones.impresoras.index'));

        $this->assertSame(1, DispositivoImpresora::query()->where('dip_device_uid', $deviceId)->count());

        $this->assertDatabaseHas('tbl_dispositivo_impresoras_dip', [
            'dip_device_uid' => $deviceId,
            'dip_nombre_impresora' => 'EPSON TM-T20',
            'dip_tipo_conexion' => 'red',
            'dip_host' => '192.168.1.35',
            'dip_puerto' => 9100,
            'dip_updated_by_usr_id' => $otroUsuario->usr_id,
        ]);
    }
}
