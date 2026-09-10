<?php

namespace Tests\Unit\Reportes;

use App\Services\Reportes\ComisionV2MovimientoService;
use App\Services\Reportes\ComisionV2Service;
use PHPUnit\Framework\TestCase;

class ComisionV2ServiceTest extends TestCase
{
    private ComisionV2Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComisionV2Service(new ComisionV2MovimientoService);
    }

    public function test_no_comisiona_antes_del_cien_por_ciento(): void
    {
        $resultado = $this->service->calcularFila(999.90, 1000, 33, 0.9);

        $this->assertSame(99.99, $resultado['cumplimiento']);
        $this->assertSame(0.0, $resultado['comision']);
    }

    public function test_comisiona_desde_el_cien_por_ciento_con_factor_del_33(): void
    {
        $this->assertSame(2.97, $this->service->calcularFila(1000, 1000, 33, 0.9)['comision']);
        $this->assertSame(0.0, $this->service->calcularFila(1000, 1000, 33, 0)['comision']);
        $this->assertSame(3.3, $this->service->calcularFila(1000, 1000, 33, 1)['comision']);
    }
}
