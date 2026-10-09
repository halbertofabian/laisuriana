<?php

namespace Tests\Feature\Reportes;

use App\Models\Almacen;
use App\Models\BitacoraAccion;
use App\Models\ComisionV2Departamento;
use App\Models\Linea;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionV2MovimientoService;
use App\Services\Reportes\ComisionV2Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Flujo de Administración: referencia histórica en vivo, metas manuales, ajustes y periodos cerrados. */
class ComisionExperienciaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $segundo;

    private Sucursal $sucursal;

    private Almacen $almacen;

    private Almacen $otroAlmacen;

    private int $linea;

    private int $departamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 9));
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->sucursal = Sucursal::query()->firstOrFail();
        $this->almacen = $this->sucursal->almacenes()->firstOrFail();
        $this->otroAlmacen = Almacen::query()->create([
            'alm_scl_id' => $this->sucursal->scl_id, 'alm_nombre' => 'Segundo', 'alm_clave' => 'SEGUNDO',
            'alm_tal_id' => $this->almacen->alm_tal_id, 'alm_estatus' => 'activo',
        ]);
        $this->linea = (int) Linea::query()->firstOrFail()->lna_id;
        $this->departamento = (int) ComisionV2Departamento::query()->where('cmd_clave', 'ROPA')->value('cmd_id');
        $this->segundo = Usuario::query()->create([
            'usr_usuario' => 'segundo-vendedor', 'usr_nombre' => 'Segundo vendedor',
            'usr_password' => Hash::make('secret'), 'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $this->segundo->usr_id, 'usc_scl_id' => $this->sucursal->scl_id,
            'usc_es_predeterminada' => true, 'usc_estatus' => 'activo', 'usc_deleted' => false,
        ]);
        $this->actingAs($this->admin)->withSession(['sucursal_activa_id' => $this->sucursal->scl_id]);
    }

    public function test_vista_previa_con_historico_completo_coincide_con_lo_guardado(): void
    {
        $this->capturar($this->almacen->alm_id, 500000, 50000);
        $this->capturar($this->otroAlmacen->alm_id, 300000, 30000);
        $datos = $this->configuracion(incremento: 10);

        $vista = $this->postJson($this->rutaVistaPrevia(), $datos)->assertOk()->json();
        $departamento = $vista['departamentos'][$this->departamento];
        $this->assertSame('2025-10', $vista['referencia']);
        $this->assertSame('completa', $departamento['estado']);
        $this->assertSame('manual', $departamento['origen']);
        $this->assertEquals(720000, $departamento['base']);
        $this->assertEquals(720000, $departamento['promedio']);
        $this->assertEquals(792000, $departamento['sugerida']);
        $this->assertEquals(792000, $vista['vendedores'][$this->admin->usr_id]['meta']);
        $this->assertSame('comun', $vista['vendedores'][$this->admin->usr_id]['origen']);

        // La vista previa y el guardado usan la misma regla.
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasNoErrors();
        $guardado = DB::table('tbl_comision_v2_periodo_departamentos_cpd')->first();
        $this->assertEquals($departamento['sugerida'], $guardado->cpd_meta_sugerida);
        $this->assertEquals($departamento['meta_comun'], $guardado->cpd_meta_comun);
        $this->assertSame('historica', $guardado->cpd_origen_meta);
    }

    public function test_vista_previa_distingue_historico_parcial_y_sin_base(): void
    {
        $this->capturar($this->almacen->alm_id, 200000, 20000);
        $vista = $this->postJson($this->rutaVistaPrevia(), $this->configuracion())->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertSame('parcial', $vista['estado']);
        $this->assertSame(1, $vista['faltantes']);
        $faltante = collect($vista['combinaciones'])->firstWhere('fuente', 'sin_datos');
        $this->assertSame($this->otroAlmacen->alm_id, $faltante['almacen_id']);
        $this->assertEquals(180000, $vista['base']);

        $sinHistorico = $this->postJson($this->rutaVistaPrevia(), array_replace($this->configuracion(), ['periodo' => '2026-12']))
            ->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertSame('sin_base', $sinHistorico['estado']);
        $this->assertSame('base', $sinHistorico['falta']);
        $this->assertNull($sinHistorico['sugerida']);
        $this->assertNull($sinHistorico['meta_comun']);

        $sinEquipo = $this->configuracion();
        $sinEquipo['vendedores'] = [];
        $faltaEquipo = $this->postJson($this->rutaVistaPrevia(), $sinEquipo)->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertSame('vendedores', $faltaEquipo['falta']);
        $this->assertEquals(180000, $faltaEquipo['base']);
    }

    public function test_combina_pos_y_capturas_sin_sumar_la_misma_combinacion(): void
    {
        $this->capturar($this->almacen->alm_id, 1000, 100);
        $this->mock(ComisionV2MovimientoService::class, function ($mock): void {
            $mock->shouldReceive('obtenerPorLineas')->andReturnUsing(fn ($sucursal, $almacenes, $grupos) => collect([
                (object) ['departamento_periodo_id' => $grupos[$this->linea], 'almacen_id' => $this->almacen->alm_id, 'linea_id' => $this->linea, 'vendedor_id' => $this->admin->usr_id, 'importe' => 700],
                (object) ['departamento_periodo_id' => $grupos[$this->linea], 'almacen_id' => $this->otroAlmacen->alm_id, 'linea_id' => $this->linea, 'vendedor_id' => $this->admin->usr_id, 'importe' => 400],
                (object) ['departamento_periodo_id' => $grupos[$this->linea], 'almacen_id' => $this->otroAlmacen->alm_id, 'linea_id' => $this->linea, 'vendedor_id' => null, 'importe' => 50],
            ]));
        });

        $vista = $this->postJson($this->rutaVistaPrevia(), $this->configuracion())->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertSame('mixto', $vista['origen']);
        $this->assertEquals(1450, $vista['ventas']); // 1,000 capturados + 450 del POS del otro almacén.
        $this->assertEquals(150, $vista['autoservicio']);
        $manual = collect($vista['combinaciones'])->firstWhere('fuente', 'manual');
        $this->assertEquals(700, $manual['sistema_sustituido']);
    }

    public function test_meta_manual_tiene_prioridad_y_el_modo_historico_ignora_importes_anteriores(): void
    {
        $this->capturar($this->almacen->alm_id, 500000, 50000);
        $this->capturar($this->otroAlmacen->alm_id, 300000, 30000);
        $datos = $this->configuracion(incremento: 10);
        $datos['departamentos'][$this->departamento]['modo_meta'] = 'manual';
        $datos['departamentos'][$this->departamento]['meta_comun'] = 350000;

        $vista = $this->postJson($this->rutaVistaPrevia(), $datos)->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertEquals(350000, $vista['meta_comun']);
        $this->assertEquals(792000, $vista['sugerida']);
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasNoErrors();
        $this->assertSame('manual', DB::table('tbl_comision_v2_periodo_departamentos_cpd')->value('cpd_origen_meta'));
        $this->assertEquals(350000, DB::table('tbl_comision_v2_participantes_cpt')->value('cpt_meta_individual'));

        // Volver al histórico no exige borrar la meta manual escrita antes.
        $datos['departamentos'][$this->departamento]['modo_meta'] = 'historica';
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasNoErrors();
        $this->assertEquals(792000, DB::table('tbl_comision_v2_periodo_departamentos_cpd')->value('cpd_meta_comun'));
        $this->assertEquals(792000, DB::table('tbl_comision_v2_participantes_cpt')->value('cpt_meta_individual'));
    }

    public function test_cambiar_incremento_actualiza_la_meta_comun_y_conserva_ajustes_individuales(): void
    {
        $this->capturar($this->almacen->alm_id, 200000, 0);
        $datos = $this->configuracion(incremento: 0);
        $datos['vendedores'][$this->segundo->usr_id] = [
            'habilitado' => '1', 'numero' => '2', 'departamento_id' => $this->departamento,
            'meta' => 120000, 'tasa' => 0.9, 'motivo' => null,
        ];

        $this->put($this->rutaGuardar(), $datos)->assertSessionHasErrors("vendedores.{$this->segundo->usr_id}.motivo");
        $this->assertDatabaseCount('tbl_comision_v2_periodos_cmp', 0);

        $datos['vendedores'][$this->segundo->usr_id]['motivo'] = 'Mayor antigüedad';
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasNoErrors();
        $this->assertSame([100000.0, 120000.0], $this->metas());

        // El formulario muestra vacía la meta que sigue a la común y conserva el ajuste.
        $html = $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.index', ['periodo' => '2026-10']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="vendedores\['.$this->admin->usr_id.'\]\[meta\]" value=""/', $html);
        $this->assertMatchesRegularExpression('/name="vendedores\['.$this->segundo->usr_id.'\]\[meta\]" value="120000"/', $html);

        $datos['departamentos'][$this->departamento]['incremento_meta'] = 20;
        $vista = $this->postJson($this->rutaVistaPrevia(), $datos)->assertOk()->json('departamentos.'.$this->departamento);
        $this->assertEquals(100000, $vista['guardado']['meta_comun']);
        $this->assertEquals(120000, $vista['meta_comun']);
        $this->assertSame(1, $vista['ajustes']);
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasNoErrors();
        $this->assertSame([120000.0, 120000.0], $this->metas());
    }

    public function test_periodo_cerrado_muestra_resultado_definitivo_y_bloquea_cambios(): void
    {
        $datos = $this->configuracion();
        $datos['departamentos'][$this->departamento] += ['modo_meta' => 'manual', 'meta_comun' => 1000];
        $service = app(ComisionV2Service::class);
        $periodo = $service->guardar($datos, $this->sucursal->scl_id, $this->admin->usr_id);
        $service->cerrar($service->aprobar($periodo, $this->admin->usr_id), $this->admin->usr_id);

        $this->postJson($this->rutaVistaPrevia(), $datos)->assertStatus(422)->assertJsonValidationErrors('periodo');
        $this->put($this->rutaGuardar(), $datos)->assertSessionHasErrors('periodo');
        $html = $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.index', ['periodo' => '2026-10']))
            ->assertOk()->assertSee('Resultado definitivo')->assertSee('Sin ventas netas en el periodo.')->assertDontSee('data-save-button', false)->getContent();
        $this->assertStringContainsString('"locked":true', $html);
    }

    public function test_explica_por_que_una_comision_es_cero(): void
    {
        $service = app(ComisionV2Service::class);
        $this->assertSame('meta_no_alcanzada', $service->explicarResultado(999.99, 1000, 0.9, 0)['motivo']);
        $this->assertStringContainsString('$0.01', $service->explicarResultado(999.99, 1000, 0.9, 0)['explicacion']);
        $this->assertSame('tasa_cero', $service->explicarResultado(1000, 1000, 0, 0)['motivo']);
        $this->assertSame('sin_meta', $service->explicarResultado(1000, 0, 0.9, 0)['motivo']);
        $this->assertSame('sin_ventas', $service->explicarResultado(0, 1000, 0.9, 0)['motivo']);
        $this->assertSame('comisiona', $service->explicarResultado(1000, 1000, 0.9, 2.97)['motivo']);
        $this->assertNull($service->metaSugerida(0, 2, 10));
        $this->assertNull($service->metaSugerida(1000, 0, 10));
        $this->assertSame(550.0, $service->metaSugerida(1000, 2, 10));
    }

    public function test_historico_regresa_al_periodo_detecta_duplicados_y_muestra_bitacora(): void
    {
        $url = route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', [
            'volver' => '2026-10', 'almacen' => $this->otroAlmacen->alm_id, 'linea' => $this->linea,
        ]);
        $this->get($url)->assertOk()
            ->assertSee('Volver a las metas de octubre de 2026')
            ->assertSee('value="2025-10"', false)
            ->assertSee('<option value="'.$this->otroAlmacen->alm_id.'" selected>', false)
            ->assertSee('no crea ventas, movimientos de caja o inventario, ni comisiones', false);

        $captura = ['periodo' => '2025-10', 'almacen_id' => $this->almacen->alm_id, 'linea_id' => $this->linea, 'ventas_netas' => '1000', 'autoservicio' => '100', 'referencia' => 'Reporte octubre', 'volver' => '2026-10'];
        $this->post(route('desktop.operacion.gestion_configuraciones.comisiones.historico.store'), $captura)
            ->assertRedirect(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => '2025-10', 'volver' => '2026-10']))
            ->assertSessionHas('success', fn ($mensaje) => str_contains($mensaje, 'octubre de 2026'));
        $id = DB::table('tbl_comision_historicos_chv')->value('chv_id');
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['mes' => '2025-10']))
            ->assertSee('"2025-10|'.$this->almacen->alm_id.'|'.$this->linea.'":{"id":'.$id, false);

        $this->post(route('desktop.operacion.gestion_configuraciones.comisiones.historico.store'), $captura + ['id' => $id, 'version' => 1, 'motivo' => 'Se corrigió el reporte'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, BitacoraAccion::query()->where('bac_entidad_id', (string) $id)->count());
        $this->get(route('desktop.operacion.gestion_configuraciones.comisiones.historico.index', ['editar' => $id, 'volver' => '2026-10']))
            ->assertOk()->assertSee('Cambios registrados')->assertSee('Versión 2')->assertSee('Captura inicial');
    }

    public function test_vista_previa_requiere_permiso_de_configuracion(): void
    {
        $usuario = Usuario::query()->create(['usr_usuario' => 'sin-permisos', 'usr_nombre' => 'Sin permisos', 'usr_password' => 'no-login', 'usr_estatus' => 'activo']);
        $this->actingAs($usuario)->postJson($this->rutaVistaPrevia(), $this->configuracion())->assertForbidden();
    }

    private function configuracion(float $incremento = 0): array
    {
        return [
            'periodo' => '2026-10',
            'almacen_ids' => [$this->almacen->alm_id, $this->otroAlmacen->alm_id],
            'departamentos' => [$this->departamento => [
                'habilitado' => '1', 'linea_ids' => [$this->linea], 'incremento_meta' => $incremento,
            ]],
            'vendedores' => [$this->admin->usr_id => [
                'habilitado' => '1', 'numero' => '1', 'departamento_id' => $this->departamento, 'meta' => null, 'tasa' => 0.9,
            ]],
        ];
    }

    private function capturar(int $almacenId, float $ventas, float $autoservicio): void
    {
        DB::table('tbl_comision_historicos_chv')->insert([
            'chv_scl_id' => $this->sucursal->scl_id, 'chv_periodo' => '2025-10-01', 'chv_alm_id' => $almacenId,
            'chv_lna_id' => $this->linea, 'chv_ventas_netas' => $ventas, 'chv_autoservicio' => $autoservicio,
            'chv_referencia' => 'Prueba', 'chv_version' => 1,
            'chv_created_by_usr_id' => $this->admin->usr_id, 'chv_updated_by_usr_id' => $this->admin->usr_id,
            'chv_created_at' => now(), 'chv_updated_at' => now(),
        ]);
    }

    private function metas(): array
    {
        return DB::table('tbl_comision_v2_participantes_cpt')->orderBy('cpt_numero_vendedor')
            ->pluck('cpt_meta_individual')->map(fn ($meta) => (float) $meta)->all();
    }

    private function rutaVistaPrevia(): string
    {
        return route('desktop.operacion.gestion_configuraciones.comisiones.vista_previa');
    }

    private function rutaGuardar(): string
    {
        return route('desktop.operacion.gestion_configuraciones.comisiones.update');
    }
}
