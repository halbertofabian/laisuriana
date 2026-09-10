<?php

namespace Tests\Feature\Reportes;

use App\Models\ComisionPeriodo;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\Reportes\ReporteConsultaService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComisionCalculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_periodo_legacy_sigue_disponible_como_historico_de_solo_consulta(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sucursal = Sucursal::query()->firstOrFail();
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $mes = now()->subMonths(2)->startOfMonth();
        ComisionPeriodo::query()->create([
            'cpe_scl_id' => $sucursal->scl_id,
            'cpe_periodo' => $mes,
            'cpe_factor_comisionable' => 33,
            'cpe_tasa_general' => 0.9,
            'cpe_cumplimiento_minimo' => 100,
            'cpe_estatus' => 'cerrado',
        ]);

        $reporte = app(ReporteConsultaService::class)->consultar('ventas-comisiones', $admin, $sucursal->scl_id, [
            'desde' => $mes->toDateString(),
            'hasta' => $mes->copy()->endOfMonth()->toDateString(),
        ]);

        $this->assertSame('cerrado', $reporte['estado_periodo']);
        $this->assertContains('Bono', $reporte['encabezados']);
        $this->assertSame(0, $reporte['total_registros']);
        $this->assertDatabaseCount('tbl_comision_v2_periodos_cmp', 0);
    }
}
