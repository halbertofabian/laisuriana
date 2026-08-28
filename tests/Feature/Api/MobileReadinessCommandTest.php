<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MobileReadinessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_verificacion_confirma_que_el_api_mobile_esta_listo(): void
    {
        $this->artisan('mobile:verificar-api')
            ->expectsOutputToContain('API móvil listo para Suriana Vendedor.')
            ->assertSuccessful();
    }

    public function test_verificacion_falla_si_capacitor_no_esta_permitido_por_cors(): void
    {
        Config::set('cors.allowed_origins', []);

        $this->artisan('mobile:verificar-api')
            ->expectsOutputToContain('El API móvil todavía no está listo.')
            ->assertFailed();
    }
}
