<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class MobileHealthTest extends TestCase
{
    public function test_health_confirma_que_el_api_mobile_esta_disponible(): void
    {
        $this->getJson('/api/v1/mobile/health')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson([
                'data' => [
                    'status' => 'ok',
                    'service' => 'suriana-mobile-api',
                    'version' => 'v1',
                ],
            ]);
    }

    public function test_capacitor_puede_hacer_preflight_al_api_mobile(): void
    {
        $this->call('OPTIONS', '/api/v1/mobile/health', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost')
            ->assertHeader('Access-Control-Allow-Methods');
    }
}
