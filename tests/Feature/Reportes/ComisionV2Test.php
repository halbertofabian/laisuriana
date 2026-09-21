<?php

namespace Tests\Feature\Reportes;

use App\Models\BitacoraAccion;
use App\Models\ComisionV2Departamento;
use App\Models\ComisionV2Periodo;
use App\Models\Linea;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use App\Models\UsuarioRol;
use App\Services\Reportes\ComisionV2MovimientoService;
use App\Services\Reportes\ComisionV2Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComisionV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_modulo_mensual_amigable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('desktop.operacion.gestion_configuraciones.comisiones.index'))
            ->assertOk()
            ->assertSee('Configura un periodo sin complicaciones')
            ->assertSee('¿Qué almacenes participan?')
            ->assertSee('Selecciona el equipo del mes')
            ->assertSee('Revisa antes de publicar')
            ->assertSee('data-step-panel="1"', false)
            ->assertSee('data-step-panel="4"', false);
    }

    public function test_configura_aprueba_cierra_y_conserva_el_historico_anterior(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $historicoAnterior = DB::table('tbl_comision_resultados_crs')->count();
        $datos = $this->configuracion($admin, $sucursal);

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), $datos)
            ->assertRedirect();

        $periodo = ComisionV2Periodo::query()->firstOrFail();
        $this->assertSame('borrador', $periodo->cmp_estatus);
        $this->assertDatabaseHas('tbl_comision_v2_participantes_cpt', [
            'cpt_cmp_id' => $periodo->cmp_id,
            'cpt_usr_id' => $admin->usr_id,
            'cpt_meta_individual' => 1000,
            'cpt_tasa_comision' => 0.9,
        ]);
        $this->assertSame($historicoAnterior, DB::table('tbl_comision_resultados_crs')->count());

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertSame('aprobado', $periodo->fresh()->cmp_estatus);

        $datos['motivo_cambio'] = 'Ajuste autorizado por administración';
        $datos['vendedores'][$admin->usr_id]['tasa'] = 0.8;
        $datos['vendedores'][$admin->usr_id]['motivo'] = 'Tasa especial autorizada';
        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), $datos)
            ->assertRedirect();
        $auditoria = BitacoraAccion::query()->where('bac_accion', 'comisiones.v2.configurar')->latest('bac_id')->firstOrFail();
        $this->assertNotNull($auditoria->bac_payload['antes']);
        $this->assertSame(0.8, $auditoria->bac_payload['despues']['vendedores'][0]['tasa']);

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.cerrar'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertSame('cerrado', $periodo->fresh()->cmp_estatus);
        $this->assertDatabaseCount('tbl_comision_v2_resultados_cmr', 1);

        $reporte = $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->getJson(route('reportes.data', [
                'reporte' => 'ventas-comisiones',
                'desde' => now()->startOfMonth()->toDateString(),
                'hasta' => now()->endOfMonth()->toDateString(),
            ]));
        $reporte->assertOk()->assertJsonPath('estado_periodo', 'cerrado')->assertJsonPath('total_registros', 1);
        $this->assertNotContains('Bono', $reporte->json('encabezados'));

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), $datos)
            ->assertSessionHasErrors('periodo');
        $this->assertSame($historicoAnterior, DB::table('tbl_comision_resultados_crs')->count());
    }

    public function test_api_de_avance_es_privada_limitada_y_no_expone_importes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $datos = $this->configuracion($admin, $sucursal);

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), $datos);
        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')]);
        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.cerrar'), ['periodo' => now()->format('Y-m')]);
        DB::table('tbl_comision_v2_resultados_cmr')->update(['cmr_cumplimiento' => 125.25]);

        $token = $admin->createToken('test', ['mobile:commission-progress'])->plainTextToken;
        $respuesta = $this->withToken($token)->getJson('/api/v1/mobile/commission-progress?branch_id='.$sucursal->scl_id);
        $respuesta->assertOk()->assertExactJson(['data' => [
            'estado' => 'cerrado',
            'porcentaje' => 100,
            'mensaje' => '¡Meta alcanzada! Gran trabajo en equipo.',
        ]]);
        $contenido = mb_strtolower(json_encode($respuesta->json(), JSON_UNESCAPED_UNICODE));
        foreach (['meta_individual', 'ventas', 'comision', 'tasa'] as $campoPrivado) {
            $this->assertStringNotContainsString($campoPrivado, $contenido);
        }

        $ajeno = Usuario::query()->create([
            'usr_usuario' => 'otro-vendedor',
            'usr_nombre' => 'Otro vendedor',
            'usr_password' => Hash::make('secret'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $ajeno->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);
        UsuarioRol::query()->create([
            'url_usr_id' => $ajeno->usr_id,
            'url_rol_id' => Rol::query()->where('rol_nombre', 'Vendedor piso')->value('rol_id'),
            'url_estatus' => 'activo',
            'url_deleted' => false,
        ]);
        $tokenAjeno = $ajeno->createToken('test', ['mobile:commission-progress'])->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($tokenAjeno)
            ->getJson('/api/v1/mobile/commission-progress?branch_id='.$sucursal->scl_id)
            ->assertExactJson(['data' => [
                'estado' => 'sin_meta',
                'porcentaje' => null,
                'mensaje' => 'Tu meta aún no está disponible.',
            ]]);
    }

    public function test_propone_meta_historica_sin_autoservicio_y_con_equipo_congelado(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $segundo = Usuario::query()->create([
            'usr_usuario' => 'segundo-vendedor',
            'usr_nombre' => 'Segundo vendedor',
            'usr_password' => Hash::make('secret'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $segundo->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);
        $datos = $this->configuracion($admin, $sucursal);
        $departamentoId = array_key_first($datos['departamentos']);
        $datos['departamentos'][$departamentoId]['incremento_meta'] = 10;
        $datos['departamentos'][$departamentoId]['meta_comun'] = null;
        $datos['vendedores'][$admin->usr_id]['meta'] = null;
        $datos['vendedores'][$segundo->usr_id] = [
            'habilitado' => '1', 'numero' => '2', 'departamento_id' => $departamentoId,
            'meta' => null, 'tasa' => 0.9, 'motivo' => null,
        ];

        $this->mock(ComisionV2MovimientoService::class, function ($mock): void {
            $mock->shouldReceive('obtener')->once()->andReturnUsing(function ($periodo) {
                $departamentoPeriodoId = $periodo->departamentos()->value('cpd_id');
                return collect([
                    (object) ['departamento_periodo_id' => $departamentoPeriodoId, 'vendedor_id' => 1, 'importe' => 1000],
                    (object) ['departamento_periodo_id' => $departamentoPeriodoId, 'vendedor_id' => null, 'importe' => 200],
                ]);
            });
        });

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), $datos)
            ->assertRedirect();

        $departamento = DB::table('tbl_comision_v2_periodo_departamentos_cpd')->first();
        $this->assertSame('historica', $departamento->cpd_origen_meta);
        $this->assertSame(1200.0, (float) $departamento->cpd_ventas_historicas);
        $this->assertSame(200.0, (float) $departamento->cpd_autoservicio_historico);
        $this->assertSame(1000.0, (float) $departamento->cpd_base_historica);
        $this->assertSame(2, (int) $departamento->cpd_vendedores_congelados);
        $this->assertSame(550.0, (float) $departamento->cpd_meta_sugerida);
        $this->assertSame([550.0, 550.0], DB::table('tbl_comision_v2_participantes_cpt')->orderBy('cpt_numero_vendedor')->pluck('cpt_meta_individual')->map(fn ($meta) => (float) $meta)->all());

        $this->actingAs($admin)->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertSame(2, (int) DB::table('tbl_comision_v2_periodo_departamentos_cpd')->value('cpd_vendedores_congelados'));
    }

    public function test_un_centavo_faltante_no_comisiona_ni_anuncia_meta_alcanzada_al_estimar_o_cerrar(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $datos = $this->configuracion($admin, $sucursal);

        $this->mock(ComisionV2MovimientoService::class, function ($mock) use ($admin, $datos): void {
            $mock->shouldReceive('obtener')->andReturnUsing(function ($periodo, $desde) use ($admin, $datos) {
                if ($desde->format('Y-m') !== $datos['periodo']) {
                    return collect();
                }

                return collect([(object) [
                    'vendedor_id' => $admin->usr_id,
                    'departamento_periodo_id' => $periodo->departamentos()->value('cpd_id'),
                    'almacen_id' => $datos['almacen_ids'][0],
                    'almacen_nombre' => 'Almacén de prueba',
                    'linea_id' => array_values($datos['departamentos'])[0]['linea_ids'][0],
                    'linea_nombre' => 'Línea de prueba',
                    'venta_bruta' => 999.99,
                    'descuentos' => 0,
                    'devoluciones' => 0,
                    'importe' => 999.99,
                ]]);
            });
        });

        $service = app(ComisionV2Service::class);
        $periodo = $service->guardar($datos, $sucursal->scl_id, $admin->usr_id);
        $periodo = $service->aprobar($periodo, $admin->usr_id);

        $estimacion = $service->estimacionAdministrativa($periodo)->sole();
        $this->assertSame(0.0, $estimacion->comision);
        $this->assertSame(99.99, $estimacion->cumplimiento);
        $avance = $service->avancePropio($admin->usr_id, $sucursal->scl_id);
        $this->assertSame('en_progreso', $avance['estado']);
        $this->assertSame(99.99, $avance['porcentaje']);
        $this->assertStringNotContainsString('Meta alcanzada', $avance['mensaje']);

        $reporte = $service->reporte($periodo);
        $this->assertSame('No generó comisión porque no alcanzó el 100% de la meta.', $reporte['detalles']['1']['resumen']['explicacion_tasa']);

        $service->cerrar($periodo, $admin->usr_id);
        $resultado = $periodo->resultados()->sole();
        $this->assertSame(0.0, (float) $resultado->cmr_comision);
        $this->assertSame(99.99, (float) $resultado->cmr_cumplimiento);
        $avanceCerrado = $service->avancePropio($admin->usr_id, $sucursal->scl_id);
        $this->assertSame('cerrado', $avanceCerrado['estado']);
        $this->assertSame(99.99, $avanceCerrado['porcentaje']);
        $this->assertStringNotContainsString('Meta alcanzada', $avanceCerrado['mensaje']);
    }

    private function configuracion(Usuario $vendedor, Sucursal $sucursal): array
    {
        $almacen = $sucursal->almacenes()->firstOrFail();
        $linea = Linea::query()->firstOrFail();
        $departamento = ComisionV2Departamento::query()->where('cmd_clave', 'ROPA')->firstOrFail();

        return [
            'periodo' => now()->format('Y-m'),
            'almacen_ids' => [$almacen->alm_id],
            'departamentos' => [
                $departamento->cmd_id => [
                    'habilitado' => '1',
                    'linea_ids' => [$linea->lna_id],
                    'incremento_meta' => 0,
                    'meta_comun' => 1000,
                ],
            ],
            'vendedores' => [
                $vendedor->usr_id => [
                    'habilitado' => '1',
                    'numero' => '1',
                    'departamento_id' => $departamento->cmd_id,
                    'meta' => 1000,
                    'tasa' => 0.9,
                    'motivo' => null,
                ],
            ],
        ];
    }
}
