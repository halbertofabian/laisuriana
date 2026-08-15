<?php

namespace Tests\Feature\Reportes;

use App\Models\BitacoraAccion;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ComisionGrupo;
use App\Models\ComisionPeriodo;
use App\Models\ComisionPeriodoGrupo;
use App\Models\ComisionResultado;
use App\Models\ComisionResultadoDetalle;
use App\Models\ComisionVendedor;
use App\Models\Permiso;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Rol;
use App\Models\RolPermiso;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionCalculoService;
use App\Services\Reportes\ReporteExportacionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComisionCalculoTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_pantallas_de_configuracion_y_reporte_respetan_el_shell_desktop(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('desktop.operacion.gestion_configuraciones.comisiones.index'))
            ->assertOk()
            ->assertSee('Preparar comisión mensual')
            ->assertSee('desktop-pane', false)
            ->assertSee('Reglas generales')
            ->assertSee('Grupos y líneas')
            ->assertSee('Vendedores')
            ->assertSee('data-commission-panel="1"', false)
            ->assertSee('data-seller-details', false);

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('reportes.show', ['reporte' => 'ventas-comisiones']))
            ->assertOk()
            ->assertSee('Comisiones por vendedor')
            ->assertSee('desktop-rep-table', false)
            ->assertSee('filter-commission-group', false)
            ->assertSee('filter-commission-status', false)
            ->assertSee('data-commission-detail', false);
    }

    public function test_guarda_relaciones_configurables_del_periodo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $almacen = $sucursal->almacenes()->firstOrFail();
        $sku = ProductoSku::query()->with('producto')->firstOrFail();
        $ropa = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
        $telas = ComisionGrupo::query()->where('cgr_clave', 'TELAS')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), [
                'periodo' => now()->format('Y-m'),
                'factor_comisionable' => 33,
                'tasa_general' => 0.9,
                'cumplimiento_minimo' => 100,
                'almacen_ids' => [$almacen->alm_id],
                'grupos' => [
                    $ropa->cgr_id => ['linea_ids' => [$sku->producto->prd_lna_id], 'vendedores_promedio' => 1, 'incremento_meta' => 8],
                    $telas->cgr_id => ['linea_ids' => [], 'vendedores_promedio' => 1, 'incremento_meta' => 5],
                ],
                'vendedores' => [
                    $admin->usr_id => [
                        'habilitado' => 1,
                        'numero' => '99',
                        'grupo_id' => $ropa->cgr_id,
                        'ajuste_tasa' => 0.1,
                        'tasa_final' => 1,
                        'bono' => 50,
                        'motivo' => 'Buen desempeño',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Configuración guardada. El periodo está listo para calcularse.');
        $periodo = ComisionPeriodo::query()->firstOrFail();
        $this->assertDatabaseHas('tbl_comision_periodo_almacenes_cpa', ['cpa_cpe_id' => $periodo->cpe_id, 'cpa_alm_id' => $almacen->alm_id]);
        $this->assertDatabaseHas('tbl_comision_periodo_lineas_cpl', ['cpl_cpe_id' => $periodo->cpe_id, 'cpl_cgr_id' => $ropa->cgr_id, 'cpl_lna_id' => $sku->producto->prd_lna_id]);
        $this->assertDatabaseHas('tbl_comision_periodo_vendedores_cpv', ['cpv_cpe_id' => $periodo->cpe_id, 'cpv_numero_vendedor' => '99']);
        $this->assertDatabaseHas('tbl_comision_ajustes_vendedor_cav', ['cav_cpe_id' => $periodo->cpe_id, 'cav_tasa_final' => 1, 'cav_bono' => 50]);

        $auditoria = BitacoraAccion::query()->where('bac_accion', 'comisiones.configurar')->latest('bac_id')->firstOrFail();
        $this->assertNull(data_get($auditoria->bac_payload, 'antes'));
        $this->assertSame(33.0, (float) data_get($auditoria->bac_payload, 'despues.factor_comisionable'));
        $this->assertSame('99', data_get($auditoria->bac_payload, 'despues.vendedores.0.numero'));
        $this->assertSame(1.0, (float) data_get($auditoria->bac_payload, 'despues.vendedores.0.tasa_final'));

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('desktop.operacion.gestion_configuraciones.comisiones.index', ['periodo' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Pendiente de calcular.')
            ->assertSee('Calcular comisión');
    }

    public function test_valida_rangos_lineas_duplicadas_y_vendedores_sin_grupo(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::query()->firstOrFail();
        $almacen = $sucursal->almacenes()->firstOrFail();
        $lineaId = ProductoSku::query()->with('producto')->firstOrFail()->producto->prd_lna_id;
        $ropa = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
        $telas = ComisionGrupo::query()->where('cgr_clave', 'TELAS')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->from(route('desktop.operacion.gestion_configuraciones.comisiones.index'))
            ->put(route('desktop.operacion.gestion_configuraciones.comisiones.update'), [
                'periodo' => now()->format('Y-m'),
                'factor_comisionable' => 33,
                'tasa_general' => 0.9,
                'cumplimiento_minimo' => 100,
                'almacen_ids' => [$almacen->alm_id],
                'grupos' => [
                    $ropa->cgr_id => ['linea_ids' => [$lineaId], 'vendedores_promedio' => 1, 'incremento_meta' => 7],
                    $telas->cgr_id => ['linea_ids' => [$lineaId], 'vendedores_promedio' => 1, 'incremento_meta' => 13],
                ],
                'vendedores' => [
                    $admin->usr_id => ['habilitado' => 1, 'numero' => 'QA-SIN-GRUPO'],
                ],
            ])
            ->assertRedirect(route('desktop.operacion.gestion_configuraciones.comisiones.index'))
            ->assertSessionHasErrors([
                "grupos.{$ropa->cgr_id}.incremento_meta",
                "grupos.{$telas->cgr_id}.incremento_meta",
                "grupos.{$telas->cgr_id}.linea_ids",
                "vendedores.{$admin->usr_id}.grupo_id",
            ]);

        $this->assertDatabaseCount('tbl_comision_periodos_cpe', 0);
    }

    public function test_separa_permisos_de_calcular_recalcular_y_exportar_y_registra_auditoria(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sucursal = Sucursal::query()->firstOrFail();
        $almacen = $sucursal->almacenes()->firstOrFail();
        $grupo = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
        $periodo = ComisionPeriodo::query()->create([
            'cpe_scl_id' => $sucursal->scl_id,
            'cpe_periodo' => now()->startOfMonth(),
            'cpe_factor_comisionable' => 33,
            'cpe_tasa_general' => 0.9,
            'cpe_cumplimiento_minimo' => 100,
            'cpe_estatus' => 'borrador',
        ]);
        $periodo->almacenes()->attach($almacen->alm_id);
        ComisionPeriodoGrupo::query()->create([
            'cpg_cpe_id' => $periodo->cpe_id,
            'cpg_cgr_id' => $grupo->cgr_id,
            'cpg_vendedores_promedio' => 1,
            'cpg_incremento_meta' => 8,
        ]);

        $calculador = $this->crearUsuarioConPermisos('calculador-comision', ['comisiones.ver', 'comisiones.calcular'], $sucursal);
        $this->actingAs($calculador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertSame('calculado', $periodo->refresh()->cpe_estatus);
        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', ['bac_usr_id' => $calculador->usr_id, 'bac_accion' => 'comisiones.calcular']);

        $this->actingAs($calculador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')])
            ->assertForbidden();

        $recalculador = $this->crearUsuarioConPermisos('recalculador-comision', ['comisiones.ver', 'comisiones.recalcular'], $sucursal);
        $this->actingAs($recalculador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.calcular'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', ['bac_usr_id' => $recalculador->usr_id, 'bac_accion' => 'comisiones.recalcular']);

        $this->actingAs($recalculador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('reportes.exportar', ['reporte' => 'ventas-comisiones', 'formato' => 'csv', 'desde' => now()->startOfMonth()->toDateString()]))
            ->assertForbidden();

        $perfilAuditoria = ComisionVendedor::query()->create([
            'cve_usr_id' => $recalculador->usr_id,
            'cve_cgr_id' => $grupo->cgr_id,
            'cve_numero' => 'AUD-1',
            'cve_estatus' => 'activo',
        ]);
        ComisionResultado::query()->create([
            'crs_cpe_id' => $periodo->cpe_id,
            'crs_cve_id' => $perfilAuditoria->cve_id,
            'crs_cgr_id' => $grupo->cgr_id,
            'crs_numero_vendedor' => 'AUD-1',
            'crs_nombre_vendedor' => $recalculador->usr_nombre,
            'crs_grupo_nombre' => $grupo->cgr_nombre,
        ]);

        $cerrador = $this->crearUsuarioConPermisos('cerrador-comision', ['comisiones.ver', 'comisiones.cerrar'], $sucursal);
        $this->actingAs($cerrador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->post(route('reportes.comisiones.cerrar'), ['periodo' => now()->format('Y-m')])
            ->assertRedirect();
        $this->assertSame('cerrado', $periodo->refresh()->cpe_estatus);
        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', ['bac_usr_id' => $cerrador->usr_id, 'bac_accion' => 'comisiones.cerrar']);

        $exportador = $this->crearUsuarioConPermisos('exportador-comision', ['comisiones.ver', 'comisiones.exportar'], $sucursal);
        $this->actingAs($exportador)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('reportes.exportar', ['reporte' => 'ventas-comisiones', 'formato' => 'csv', 'desde' => now()->startOfMonth()->toDateString()]))
            ->assertOk();
        $this->assertDatabaseHas('tbl_bitacora_acciones_bac', ['bac_usr_id' => $exportador->usr_id, 'bac_accion' => 'comisiones.exportar_csv']);

        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $sucursal->scl_id])
            ->get(route('seguridad.bitacora.acciones', ['accion' => 'comisiones.exportar_csv']))
            ->assertOk()
            ->assertJsonPath('data.0.evento', 'Exportación de comisiones (CSV)')
            ->assertJsonPath('data.0.detalle', 'Se exportó el reporte de comisiones del periodo '.now()->format('Y-m').' en formato CSV con 1 registro.');
    }

    public function test_calcula_venta_neta_descuenta_sin_atencion_y_aplica_factor(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sucursal = Sucursal::query()->firstOrFail();
        $almacen = $sucursal->almacenes()->firstOrFail();
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $vendedor = Usuario::query()->create([
            'usr_usuario' => 'vendedor-prueba',
            'usr_nombre' => 'Vendedor Prueba',
            'usr_password' => Hash::make('secret'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $vendedor->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
        ]);

        $caja = Caja::query()->create([
            'caj_scl_id' => $sucursal->scl_id,
            'caj_alm_id' => $almacen->alm_id,
            'caj_nombre' => 'Caja prueba comisión',
            'caj_clave' => 'CAJ-COMISION',
            'caj_estatus' => 'activo',
        ]);
        $sesion = CajaSesion::query()->create([
            'cse_caj_id' => $caja->caj_id,
            'cse_scl_id' => $sucursal->scl_id,
            'cse_usr_apertura_id' => $admin->usr_id,
            'cse_monto_apertura' => 0,
            'cse_abierta_at' => now(),
            'cse_estatus' => 'activa',
        ]);
        $sku = ProductoSku::query()->with('producto')->firstOrFail();
        $grupo = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
        $grupo->lineas()->attach($sku->producto->prd_lna_id);
        $perfil = ComisionVendedor::query()->create([
            'cve_usr_id' => $vendedor->usr_id,
            'cve_cgr_id' => $grupo->cgr_id,
            'cve_numero' => '5',
            'cve_estatus' => 'activo',
        ]);

        $periodo = ComisionPeriodo::query()->create([
            'cpe_scl_id' => $sucursal->scl_id,
            'cpe_periodo' => now()->startOfMonth(),
            'cpe_factor_comisionable' => 33,
            'cpe_tasa_general' => 0.9,
            'cpe_cumplimiento_minimo' => 100,
            'cpe_estatus' => 'borrador',
        ]);
        $periodo->almacenes()->attach($almacen->alm_id);
        \Illuminate\Support\Facades\DB::table('tbl_comision_periodo_lineas_cpl')->insert([
            'cpl_cpe_id' => $periodo->cpe_id,
            'cpl_cgr_id' => $grupo->cgr_id,
            'cpl_lna_id' => $sku->producto->prd_lna_id,
        ]);
        \Illuminate\Support\Facades\DB::table('tbl_comision_periodo_vendedores_cpv')->insert([
            'cpv_cpe_id' => $periodo->cpe_id,
            'cpv_cve_id' => $perfil->cve_id,
            'cpv_cgr_id' => $grupo->cgr_id,
            'cpv_numero_vendedor' => '5',
        ]);
        ComisionPeriodoGrupo::query()->create([
            'cpg_cpe_id' => $periodo->cpe_id,
            'cpg_cgr_id' => $grupo->cgr_id,
            'cpg_vendedores_promedio' => 2,
            'cpg_incremento_meta' => 0,
        ]);

        $this->crearVenta($sesion, $caja, $sucursal, $almacen, $admin, $sku, $vendedor->usr_id, 1000, 100, 'VTA-COM-001');
        $this->crearVenta($sesion, $caja, $sucursal, $almacen, $admin, $sku, null, 500, 0, 'VTA-COM-002');

        $service = new ComisionCalculoService;
        $total = $service->calcular($periodo, $admin->usr_id);

        $this->assertSame(1, $total);
        $resultado = ComisionResultado::query()->where('crs_cve_id', $perfil->cve_id)->firstOrFail();
        $this->assertSame('900.00', $resultado->crs_ventas_totales);
        $this->assertSame('450.00', $resultado->crs_meta);
        $this->assertSame('200.00', $resultado->crs_cumplimiento);
        $this->assertSame('297.00', $resultado->crs_base_comisionable);
        $this->assertSame('2.67', $resultado->crs_comision);

        $detalle = ComisionResultadoDetalle::query()->where('crd_crs_id', $resultado->crs_id)->firstOrFail();
        $this->assertSame('1000.00', $detalle->crd_venta_bruta);
        $this->assertSame('100.00', $detalle->crd_descuentos);
        $this->assertSame('0.00', $detalle->crd_devoluciones);
        $this->assertSame('900.00', $detalle->crd_venta_neta);

        $grupoPeriodo = ComisionPeriodoGrupo::query()->where('cpg_cpe_id', $periodo->cpe_id)->firstOrFail();
        $this->assertSame('1400.00', $grupoPeriodo->cpg_ventas_grupo);
        $this->assertSame('500.00', $grupoPeriodo->cpg_ventas_sin_atencion);
        $this->assertSame('900.00', $grupoPeriodo->cpg_base_meta);

        $reporte = $service->reporte($sucursal->scl_id, now());
        $this->assertSame(
            ['No. vendedor', 'Nombre', 'Ventas totales', 'Porcentaje', 'Comisión', 'Bono'],
            array_slice($reporte['encabezados'], 0, 6),
        );
        $this->assertSame(1000.0, $reporte['detalles']['5']['resumen']['venta_bruta']);
        $this->assertSame(100.0, $reporte['detalles']['5']['resumen']['descuentos']);
        $this->assertSame(500.0, $reporte['detalles']['5']['resumen']['ventas_sin_atencion']);
        $this->assertCount(1, $reporte['detalle_exportacion']);

        $this->assertCount(1, $service->reporte($sucursal->scl_id, now(), ['usuario_id' => $vendedor->usr_id])['rows']);
        $this->assertCount(0, $service->reporte($sucursal->scl_id, now(), ['grupo_id' => 999999])['rows']);
        $this->assertCount(0, $service->reporte($sucursal->scl_id, now(), ['estado' => 'cerrado'])['rows']);

        $reporteExportable = [
            'titulo' => 'Comisiones por vendedor',
            'sucursal' => $sucursal->scl_nombre,
            'desde' => now()->startOfMonth()->toDateString(),
            'hasta' => now()->endOfMonth()->toDateString(),
            'generado_por' => $admin->usr_nombre,
            ...$reporte,
        ];
        $xlsx = app(ReporteExportacionService::class)->xlsx($reporteExportable);
        $pdf = app(ReporteExportacionService::class)->pdf($reporteExportable);
        $this->assertStringStartsWith('PK', $xlsx);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    private function crearVenta($sesion, $caja, $sucursal, $almacen, $admin, $sku, ?int $vendedorId, float $importe, float $descuentoGlobal, string $folio): void
    {
        $venta = PosVenta::query()->create([
            'psv_folio' => $folio,
            'psv_cse_id' => $sesion->cse_id,
            'psv_caj_id' => $caja->caj_id,
            'psv_scl_id' => $sucursal->scl_id,
            'psv_alm_id' => $almacen->alm_id,
            'psv_usr_id' => $admin->usr_id,
            'psv_tipo_operacion' => 'venta',
            'psv_estatus' => 'cobrada',
            'psv_subtotal' => $importe,
            'psv_descuento' => $descuentoGlobal,
            'psv_total' => $importe - $descuentoGlobal,
            'psv_pagado' => $importe - $descuentoGlobal,
            'psv_cambio' => 0,
            'psv_fecha_cobro' => now(),
        ]);
        PosVentaDetalle::query()->create([
            'pvd_psv_id' => $venta->psv_id,
            'pvd_psk_id' => $sku->psk_id,
            'pvd_cantidad' => 1,
            'pvd_precio_unitario' => $importe,
            'pvd_descuento_porcentaje' => 0,
            'pvd_descuento_importe' => 0,
            'pvd_importe' => $importe,
            'pvd_usr_id' => $vendedorId,
        ]);
    }

    private function crearUsuarioConPermisos(string $usuario, array $permisos, Sucursal $sucursal): Usuario
    {
        $registro = Usuario::query()->create([
            'usr_usuario' => $usuario,
            'usr_nombre' => str($usuario)->headline()->toString(),
            'usr_password' => Hash::make('secret'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $registro->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
        ]);
        $rol = Rol::query()->create([
            'rol_nombre' => 'Rol '.str($usuario)->headline(),
            'rol_descripcion' => 'Rol de prueba para permisos de comisiones.',
            'rol_estatus' => 'activo',
        ]);
        UsuarioRol::query()->create([
            'url_usr_id' => $registro->usr_id,
            'url_rol_id' => $rol->rol_id,
            'url_estatus' => 'activo',
            'url_deleted' => false,
        ]);
        foreach (Permiso::query()->whereIn('prm_clave', $permisos)->get() as $permiso) {
            RolPermiso::query()->create([
                'rpm_rol_id' => $rol->rol_id,
                'rpm_prm_id' => $permiso->prm_id,
                'rpm_estatus' => 'activo',
                'rpm_deleted' => false,
            ]);
        }

        return $registro;
    }
}
