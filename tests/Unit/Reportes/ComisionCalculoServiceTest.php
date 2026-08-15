<?php

namespace Tests\Unit\Reportes;

use App\Services\Reportes\ComisionCalculoService;
use PHPUnit\Framework\TestCase;

class ComisionCalculoServiceTest extends TestCase
{
    public function test_reproduce_las_comisiones_del_ejemplo_con_factor_del_33_por_ciento(): void
    {
        $service = new ComisionCalculoService;

        $casos = [
            [495909.13, 0.9, 1472.85],
            [341219.72, 0.9, 1013.42],
            [373363.71, 0.7, 862.47],
            [243000.85, 1.0, 801.90],
        ];

        foreach ($casos as [$ventas, $tasa, $esperada]) {
            $resultado = $service->calcularFila($ventas, $ventas, 33, 0.9, 0, $tasa, 100);
            $this->assertSame($esperada, $resultado['comision']);
        }
    }

    public function test_no_genera_comision_si_no_alcanza_el_cumplimiento_minimo(): void
    {
        $resultado = (new ComisionCalculoService)->calcularFila(999, 1000, 33, 0.9, 0, null, 100, 50);

        $this->assertSame(99.9, $resultado['cumplimiento']);
        $this->assertSame(0.0, $resultado['comision']);
        $this->assertSame(50.0, $resultado['total_pagar']);
    }

    public function test_genera_comision_al_cumplir_exactamente_el_umbral_y_aplica_ajustes(): void
    {
        $service = new ComisionCalculoService;
        $general = $service->calcularFila(1000, 1000, 33, 0.9, 0, null, 100);
        $incrementada = $service->calcularFila(1000, 1000, 33, 0.9, 0.1, null, 100);
        $reducidaForzada = $service->calcularFila(1000, 1000, 33, 0.9, 0, 0.7, 100);

        $this->assertSame(100.0, $general['cumplimiento']);
        $this->assertSame(2.97, $general['comision']);
        $this->assertSame(1.0, $incrementada['tasa_final']);
        $this->assertSame(3.3, $incrementada['comision']);
        $this->assertSame(-0.2, $reducidaForzada['ajuste_tasa']);
        $this->assertSame(2.31, $reducidaForzada['comision']);
    }
}
