<?php

namespace Tests\Feature\Reportes;

use App\Models\Almacen;
use App\Models\ComisionV2Departamento;
use App\Models\Linea;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\Reportes\ComisionV2MovimientoService;
use App\Services\Reportes\ComisionV2Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComisionHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Sucursal $sucursal;

    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 21));
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->sucursal = Sucursal::query()->firstOrFail();
        $this->actingAs($this->admin)->withSession(['sucursal_activa_id' => $this->sucursal->scl_id]);
        $this->datos = [
            'periodo' => '2025-08', 'almacen_id' => $this->sucursal->almacenes()->firstOrFail()->alm_id,
            'linea_id' => Linea::query()->firstOrFail()->lna_id,
            'ventas_netas' => '1200.00', 'autoservicio' => '200.00', 'referencia' => 'Reporte agosto 2025',
        ];
    }

    public function test_muestra_formulario_y_guarda_con_auditoria_sin_crear_ventas(): void
    {
        $url = route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => '2025-08']);
        $this->get($url)->assertOk()->assertSee('Nueva captura mensual')->assertSee('Base para calcular metas');
        $ventas = DB::table('tbl_pos_ventas_psv')->count();
        $this->post($this->rutaGuardar(), $this->datos)->assertRedirect($url)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tbl_comision_historicos_chv', ['chv_ventas_netas' => 1200, 'chv_autoservicio' => 200, 'chv_version' => 1]);
        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', ['bac_accion' => 'comisiones.historico.guardar']);
        $this->assertSame($ventas, DB::table('tbl_pos_ventas_psv')->count());
        $this->get($url)->assertOk()->assertSee('1,000.00')->assertSee('Corregir');
    }

    public function test_rechaza_duplicados_importes_invalidos_y_meses_no_cerrados(): void
    {
        $this->post($this->rutaGuardar(), $this->datos)->assertSessionHasNoErrors();
        $this->post($this->rutaGuardar(), $this->datos)->assertSessionHasErrors('linea_id');
        foreach ([
            ['autoservicio', '1200.01'], ['ventas_netas', '-1'], ['ventas_netas', '1.001'],
            ['periodo', '2026-09'], ['periodo', '2027-01'], ['periodo', '2025-13'], ['referencia', ''],
        ] as [$campo, $valor]) {
            $this->post($this->rutaGuardar(), array_replace($this->datos, [$campo => $valor]))->assertSessionHasErrors($campo);
        }
        $this->assertDatabaseCount('tbl_comision_historicos_chv', 1);
    }

    public function test_corrige_con_motivo_y_evitar_sobrescribir_una_version_reciente(): void
    {
        $this->post($this->rutaGuardar(), $this->datos);
        $id = DB::table('tbl_comision_historicos_chv')->value('chv_id');
        $correccion = $this->datos + ['id' => $id, 'version' => 1];
        $correccion['ventas_netas'] = '1500.00';
        $this->post($this->rutaGuardar(), $correccion)->assertSessionHasErrors('motivo');
        $correccion['motivo'] = 'Se corrigió el total del reporte';
        $this->post($this->rutaGuardar(), $correccion)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tbl_comision_historicos_chv', ['chv_id' => $id, 'chv_version' => 2, 'chv_ventas_netas' => 1500]);
        $this->post($this->rutaGuardar(), $correccion)->assertSessionHasErrors('version');
        $this->assertDatabaseCount('tbl_comision_historicos_chv', 1);
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['editar' => $id]))
            ->assertOk()->assertSee('Guardar corrección');
    }

    public function test_aisla_capturas_y_almacenes_por_sucursal(): void
    {
        $this->post($this->rutaGuardar(), $this->datos);
        $id = DB::table('tbl_comision_historicos_chv')->value('chv_id');
        $otra = Sucursal::query()->create(['scl_nombre' => 'Otra sucursal', 'scl_clave' => 'OTRA', 'scl_estatus' => 'activo']);
        $this->withSession(['sucursal_activa_id' => $otra->scl_id]);
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => '2025-08']))
            ->assertOk()->assertDontSee('Reporte agosto 2025');
        $this->post($this->rutaGuardar(), $this->datos)->assertSessionHasErrors('almacen_id');
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['editar' => $id]))->assertNotFound();
        $otroAlmacen = Almacen::query()->create([
            'alm_scl_id' => $otra->scl_id, 'alm_nombre' => 'Otro almacén', 'alm_clave' => 'OTRO',
            'alm_tal_id' => Almacen::query()->firstOrFail()->alm_tal_id, 'alm_estatus' => 'activo',
        ]);
        $this->post($this->rutaGuardar(), array_replace($this->datos, [
            'id' => $id, 'version' => 1, 'motivo' => 'Intento ajeno', 'almacen_id' => $otroAlmacen->alm_id,
        ]))->assertNotFound();
    }

    public function test_requiere_permiso_de_configuracion(): void
    {
        $usuario = Usuario::query()->create(['usr_usuario' => 'sin-permisos', 'usr_nombre' => 'Sin permisos', 'usr_password' => 'no-login', 'usr_estatus' => 'activo']);
        $this->actingAs($usuario);
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index'))->assertForbidden();
        $this->post($this->rutaGuardar(), $this->datos)->assertForbidden();
        $this->assertDatabaseCount('tbl_comision_historicos_chv', 0);
    }

    public function test_el_historico_alimenta_metas_sin_duplicar_pos_ni_alterar_ventas_actuales(): void
    {
        $this->post($this->rutaGuardar(), $this->datos)->assertSessionHasNoErrors();
        $departamento = ComisionV2Departamento::query()->firstOrFail();
        $otroAlmacen = Almacen::query()->create([
            'alm_scl_id' => $this->sucursal->scl_id, 'alm_nombre' => 'Segundo', 'alm_clave' => 'SEGUNDO',
            'alm_tal_id' => Almacen::query()->firstOrFail()->alm_tal_id, 'alm_estatus' => 'activo',
        ]);
        $this->mock(ComisionV2MovimientoService::class, function ($mock) use ($otroAlmacen) {
            $mock->shouldReceive('obtener')->andReturnUsing(function ($periodo, $desde) use ($otroAlmacen) {
                $fila = [
                    'departamento_periodo_id' => $periodo->departamentos()->value('cpd_id'),
                    'vendedor_id' => $this->admin->usr_id, 'linea_id' => $this->datos['linea_id'],
                    'almacen_id' => $this->datos['almacen_id'], 'importe' => 500,
                ];
                if ($desde->year === 2025) {
                    return collect([(object) $fila, (object) array_replace($fila, ['almacen_id' => $otroAlmacen->alm_id, 'importe' => 300])]);
                }

                return collect([(object) array_replace($fila, ['importe' => 2000])]);
            });
        });
        $config = [
            'periodo' => '2026-08', 'almacen_ids' => [$this->datos['almacen_id'], $otroAlmacen->alm_id],
            'departamentos' => [$departamento->cmd_id => ['habilitado' => true, 'linea_ids' => [$this->datos['linea_id']], 'incremento_meta' => 10]],
            'vendedores' => [$this->admin->usr_id => ['habilitado' => true, 'departamento_id' => $departamento->cmd_id, 'numero' => '1', 'tasa' => 0.9]],
        ];
        $service = app(ComisionV2Service::class);
        $periodo = $service->guardar($config, $this->sucursal->scl_id, $this->admin->usr_id);
        $referencia = $periodo->departamentos->sole();
        $this->assertSame(1500.0, (float) $referencia->cpd_ventas_historicas);
        $this->assertSame(200.0, (float) $referencia->cpd_autoservicio_historico);
        $this->assertSame(1430.0, (float) $referencia->cpd_meta_sugerida);
        $this->assertSame(2000.0, $service->estimacionAdministrativa($periodo)->sole()->ventas);

        // Un cero capturado también sustituye el POS; ausencia de captura y cero son distintos.
        DB::table('tbl_comision_historicos_chv')->update(['chv_ventas_netas' => 0, 'chv_autoservicio' => 0]);
        $periodo = $service->guardar($config, $this->sucursal->scl_id, $this->admin->usr_id);
        $this->assertSame(330.0, (float) $periodo->departamentos->sole()->cpd_meta_sugerida);
    }

    private function rutaGuardar(): string
    {
        return route('desktop.operacion.gestion_configuraciones.comisiones.historico.store');
    }
}
