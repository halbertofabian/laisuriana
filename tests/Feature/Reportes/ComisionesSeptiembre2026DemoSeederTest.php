<?php

namespace Tests\Feature\Reportes;

use App\Models\Almacen;
use App\Models\ComisionV2Periodo;
use App\Models\Linea;
use App\Models\PosVenta;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use App\Services\Reportes\ComisionV2Service;
use Database\Seeders\ComisionesSeptiembre2026DemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComisionesSeptiembre2026DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulacion_calcula_historico_neto_y_limites_sin_duplicar_ni_cerrar(): void
    {
        $this->seed(DatabaseSeeder::class);
        $matriz = Sucursal::query()->where('scl_clave', 'MATRIZ')->firstOrFail();
        $tipo = TipoAlmacen::query()->where('tal_clave', 'principal')->firstOrFail();
        foreach (['I. Suriana', 'La I. Suriana'] as $i => $nombre) {
            Almacen::query()->create(['alm_scl_id' => $matriz->scl_id, 'alm_tal_id' => $tipo->tal_id, 'alm_clave' => 'SEP26-'.$i, 'alm_nombre' => $nombre, 'alm_estatus' => 'activo']);
        }
        $productos = Producto::query()->orderBy('prd_id')->limit(2)->get();
        foreach (['LNA_CABALLERO', 'LNA_TELAS'] as $i => $clave) {
            $linea = Linea::query()->firstOrCreate(['lna_clave' => $clave], ['lna_nombre' => $clave, 'lna_estatus' => 'activo']);
            $productos[$i]->update(['prd_lna_id' => $linea->lna_id]);
        }
        $this->seed(ComisionesSeptiembre2026DemoSeeder::class);
        $this->seed(ComisionesSeptiembre2026DemoSeeder::class);
        $p = ComisionV2Periodo::query()->whereDate('cmp_periodo', '2026-09-01')->firstOrFail();
        $this->assertSame('aprobado', $p->cmp_estatus);
        $this->assertSame(0, $p->resultados()->count());
        $this->assertSame(16, PosVenta::query()->where('psv_folio', 'like', 'DEMO-COM-SEP2026-%')->count());
        $this->assertSame(4, DB::table('tbl_comision_historicos_chv')->where('chv_referencia', ComisionesSeptiembre2026DemoSeeder::MARCA)->count());
        $this->assertSame(6, $p->participantes()->count());
        $deps = $p->departamentos()->get()->keyBy('cpd_departamento_nombre');
        $this->assertEquals(440000, $deps['Ropa']->cpd_ventas_historicas);
        $this->assertEquals(40000, $deps['Ropa']->cpd_autoservicio_historico);
        $this->assertEquals(4, $deps['Ropa']->cpd_vendedores_congelados);
        $this->assertEquals(110000, $deps['Ropa']->cpd_meta_sugerida);
        $this->assertEquals(66000, $deps['Telas']->cpd_meta_sugerida);
        $service = app(ComisionV2Service::class);
        $rows = $service->estimacionAdministrativa($p)->keyBy('nombre');
        $this->assertEquals(88000, $rows['DEMO Ana']->ventas); // La cancelada no suma.
        $this->assertEquals(0, $rows['DEMO Ana']->comision);
        $this->assertEquals(326.70, $rows['DEMO Bruno']->comision);
        $this->assertEquals(132000, $rows['DEMO Carla']->ventas); // Descuento y devolución restados.
        $this->assertEquals(392.04, $rows['DEMO Carla']->comision);
        $this->assertEquals(99.99, $rows['DEMO Diego']->cumplimiento);
        $this->assertEquals(0, $rows['DEMO Diego']->comision);
        $this->assertEquals(261.36, $rows['DEMO Elena']->comision);
        $this->assertEquals(0, $rows['DEMO Fabio']->comision);
        $this->assertEquals(980.10, $service->reporte($p)['kpis']['Comisiones']);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $matriz->scl_id])
            ->get('/desktop/operacion/gestion-configuraciones/comisiones?periodo=2026-09')
            ->assertOk()->assertSee('DEMO Bruno')->assertSee('110,000.00');
        // Una configuración ajena jamás se sustituye ni se eliminan sus ventas.
        $p->update(['cmp_ultimo_motivo_cambio' => 'Configuración del usuario']);
        try {
            $this->seed(ComisionesSeptiembre2026DemoSeeder::class);
            $this->fail('Debió conservar la configuración existente.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('No se reemplazó', $e->getMessage());
        }
        $this->assertSame('Configuración del usuario', $p->fresh()->cmp_ultimo_motivo_cambio);
    }
}
