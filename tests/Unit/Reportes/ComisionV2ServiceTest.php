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

    public function test_un_centavo_faltante_no_se_redondea_a_meta_alcanzada(): void
    {
        foreach ([1000.0, 338593.45] as $meta) {
            $resultado = $this->service->calcularFila($meta - 0.01, $meta, 33, 0.9);

            $this->assertSame(0.0, $resultado['comision']);
            $this->assertSame(99.99, $resultado['cumplimiento']);
        }
    }

    public function test_un_centavo_por_encima_de_la_meta_si_comisiona(): void
    {
        $resultado = $this->service->calcularFila(1000.01, 1000, 33, 0.9);

        $this->assertSame(2.97, $resultado['comision']);
        $this->assertSame(100.0, $resultado['cumplimiento']);
    }

    public function test_meta_no_positiva_o_ventas_negativas_no_generan_comision(): void
    {
        foreach ([[1000, 0], [1000, -1], [-1, 1000], [0, 1000]] as [$ventas, $meta]) {
            $resultado = $this->service->calcularFila($ventas, $meta, 33, 0.9);

            $this->assertSame(0.0, $resultado['comision']);
            $this->assertEquals(0, $resultado['cumplimiento']);
        }
    }
}
