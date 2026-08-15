<?php

namespace Tests\Feature\Reportes;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ComisionGrupo;
use App\Models\ComisionPeriodo;
use App\Models\ComisionPeriodoGrupo;
use App\Models\ComisionResultado;
use App\Models\ComisionResultadoDetalle;
use App\Models\ComisionVendedor;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionCalculoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComisionReglasNegocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplica_reglas_operativas_y_consolida_dos_almacenes_configurados(): void
    {
        $escenario = $this->prepararEscenario();

        $ventaConDescuentos = $this->crearVenta(
            $escenario,
            $escenario['almacen_a'],
            $escenario['vendedor']->usr_id,
            $escenario['sku_grupo'],
            1000,
            100,
            90,
            'VTA-REGLAS-001',
            'cobrada',
            2,
        );
        $this->crearVenta($escenario, $escenario['almacen_b'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 600, 0, 60, 'VTA-REGLAS-002');
        $this->crearVenta($escenario, $escenario['almacen_a'], null, $escenario['sku_grupo'], 300, 0, 0, 'VTA-SIN-ATENCION');
        $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 400, 0, 0, 'VTA-CANCELADA', 'cancelada');
        $this->crearVenta($escenario, $escenario['almacen_excluido'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 500, 0, 0, 'VTA-ALMACEN-EXCLUIDO');
        $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor']->usr_id, $escenario['sku_sin_grupo'], 700, 0, 0, 'VTA-LINEA-SIN-GRUPO');
        $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor_sin_grupo']->usr_id, $escenario['sku_grupo'], 200, 0, 0, 'VTA-VENDEDOR-SIN-GRUPO');

        $ventaDevueltaTotal = $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 100, 0, 0, 'VTA-DEVOLUCION-TOTAL');
        $this->crearDevolucion($escenario, $ventaConDescuentos, 1, 450, 'DEV-PARCIAL');
        $this->crearDevolucion($escenario, $ventaDevueltaTotal, 1, 100, 'DEV-TOTAL');

        $total = app(ComisionCalculoService::class)->calcular($escenario['periodo'], $escenario['admin']->usr_id);

        $this->assertSame(1, $total, 'El vendedor sin grupo no debe generar una fila de comisión.');
        $resultado = ComisionResultado::query()->where('crs_cve_id', $escenario['perfil']->cve_id)->firstOrFail();
        $this->assertSame('945.00', $resultado->crs_ventas_totales);
        $this->assertSame('572.50', $resultado->crs_meta);
        $this->assertSame('165.07', $resultado->crs_cumplimiento);
        $this->assertSame('311.85', $resultado->crs_base_comisionable);
        $this->assertSame('2.81', $resultado->crs_comision);

        $grupoPeriodo = ComisionPeriodoGrupo::query()->where('cpg_cpe_id', $escenario['periodo']->cpe_id)->firstOrFail();
        $this->assertSame('1445.00', $grupoPeriodo->cpg_ventas_grupo);
        $this->assertSame('300.00', $grupoPeriodo->cpg_ventas_sin_atencion);
        $this->assertSame('1145.00', $grupoPeriodo->cpg_base_meta);
        $this->assertSame('572.50', $grupoPeriodo->cpg_meta_individual);

        $detalles = ComisionResultadoDetalle::query()->where('crd_crs_id', $resultado->crs_id)->get();
        $this->assertCount(2, $detalles, 'Debe conservar un desglose por cada almacén incluido.');
        $this->assertSame(1700.0, round((float) $detalles->sum('crd_venta_bruta'), 2));
        $this->assertSame(250.0, round((float) $detalles->sum('crd_descuentos'), 2));
        $this->assertSame(505.0, round((float) $detalles->sum('crd_devoluciones'), 2));
        $this->assertSame(945.0, round((float) $detalles->sum('crd_venta_neta'), 2));
        $this->assertFalse(ComisionResultado::query()->where('crs_nombre_vendedor', $escenario['vendedor_sin_grupo']->usr_nombre)->exists());
    }

    public function test_un_periodo_cerrado_conserva_la_fotografia_y_no_puede_recalcularse(): void
    {
        $escenario = $this->prepararEscenario();
        $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 1000, 0, 0, 'VTA-CIERRE');
        $servicio = app(ComisionCalculoService::class);
        $servicio->calcular($escenario['periodo'], $escenario['admin']->usr_id);
        $resultadoAntes = ComisionResultado::query()->where('crs_cpe_id', $escenario['periodo']->cpe_id)->firstOrFail();
        $servicio->cerrar($escenario['periodo']->refresh(), $escenario['admin']->usr_id);

        $this->crearVenta($escenario, $escenario['almacen_a'], $escenario['vendedor']->usr_id, $escenario['sku_grupo'], 500, 0, 0, 'VTA-DESPUES-CIERRE');

        try {
            $servicio->calcular($escenario['periodo']->refresh(), $escenario['admin']->usr_id);
            $this->fail('El periodo cerrado permitió un recálculo.');
        } catch (ValidationException $exception) {
            $this->assertSame('El periodo está cerrado y no puede recalcularse.', $exception->errors()['periodo'][0]);
        }

        $resultadoDespues = ComisionResultado::query()->where('crs_cpe_id', $escenario['periodo']->cpe_id)->firstOrFail();
        $this->assertSame('cerrado', $escenario['periodo']->refresh()->cpe_estatus);
        $this->assertSame($resultadoAntes->crs_id, $resultadoDespues->crs_id);
        $this->assertSame('1000.00', $resultadoDespues->crs_ventas_totales);
    }

    public function test_distribuye_el_descuento_global_proporcionalmente_entre_partidas(): void
    {
        $escenario = $this->prepararEscenario();
        DB::table('tbl_comision_periodo_lineas_cpl')->insert([
            'cpl_cpe_id' => $escenario['periodo']->cpe_id,
            'cpl_cgr_id' => $escenario['grupo']->cgr_id,
            'cpl_lna_id' => $escenario['sku_sin_grupo']->producto->prd_lna_id,
        ]);
        [$caja, $sesion] = $this->cajaPara($escenario, $escenario['almacen_a']);
        $venta = PosVenta::query()->create([
            'psv_folio' => 'VTA-DESCUENTO-PRORRATEADO',
            'psv_cse_id' => $sesion->cse_id,
            'psv_caj_id' => $caja->caj_id,
            'psv_scl_id' => $escenario['sucursal']->scl_id,
            'psv_alm_id' => $escenario['almacen_a']->alm_id,
            'psv_usr_id' => $escenario['admin']->usr_id,
            'psv_tipo_operacion' => 'venta',
            'psv_estatus' => 'cobrada',
            'psv_subtotal' => 400,
            'psv_descuento' => 41,
            'psv_total' => 359,
            'psv_pagado' => 359,
            'psv_cambio' => 0,
            'psv_fecha_cobro' => now(),
        ]);
        foreach ([
            [$escenario['sku_grupo'], 100],
            [$escenario['sku_sin_grupo'], 300],
        ] as [$sku, $importe]) {
            PosVentaDetalle::query()->create([
                'pvd_psv_id' => $venta->psv_id,
                'pvd_psk_id' => $sku->psk_id,
                'pvd_cantidad' => 1,
                'pvd_precio_unitario' => $importe,
                'pvd_descuento_porcentaje' => 0,
                'pvd_descuento_importe' => 0,
                'pvd_importe' => $importe,
                'pvd_usr_id' => $escenario['vendedor']->usr_id,
            ]);
        }

        app(ComisionCalculoService::class)->calcular($escenario['periodo'], $escenario['admin']->usr_id);

        $resultado = ComisionResultado::query()->where('crs_cve_id', $escenario['perfil']->cve_id)->firstOrFail();
        $detalles = ComisionResultadoDetalle::query()->where('crd_crs_id', $resultado->crs_id)->get()->keyBy('crd_lna_id');
        $detalle100 = $detalles->get($escenario['sku_grupo']->producto->prd_lna_id);
        $detalle300 = $detalles->get($escenario['sku_sin_grupo']->producto->prd_lna_id);

        $this->assertSame('10.25', $detalle100->crd_descuentos);
        $this->assertSame('89.75', $detalle100->crd_venta_neta);
        $this->assertSame('30.75', $detalle300->crd_descuentos);
        $this->assertSame('269.25', $detalle300->crd_venta_neta);
        $this->assertSame('359.00', $resultado->crs_ventas_totales);
    }

    public function test_exige_almacenes_y_grupos_antes_de_calcular(): void
    {
        $escenario = $this->prepararEscenario();
        $escenario['periodo']->almacenes()->detach();

        try {
            app(ComisionCalculoService::class)->calcular($escenario['periodo']->refresh(), $escenario['admin']->usr_id);
            $this->fail('El cálculo inició sin almacenes configurados.');
        } catch (ValidationException $exception) {
            $this->assertSame('Configura al menos un almacén para calcular comisiones.', $exception->errors()['periodo'][0]);
        }

        $escenario['periodo']->almacenes()->attach($escenario['almacen_a']->alm_id);
        ComisionPeriodoGrupo::query()->where('cpg_cpe_id', $escenario['periodo']->cpe_id)->delete();

        try {
            app(ComisionCalculoService::class)->calcular($escenario['periodo']->refresh(), $escenario['admin']->usr_id);
            $this->fail('El cálculo inició sin grupos configurados.');
        } catch (ValidationException $exception) {
            $this->assertSame('Configura los grupos de comisión para el periodo.', $exception->errors()['periodo'][0]);
        }
    }

    private function prepararEscenario(): array
    {
        $this->seed(DatabaseSeeder::class);
        $sucursal = Sucursal::query()->firstOrFail();
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $almacenA = $sucursal->almacenes()->firstOrFail();
        $almacenB = $this->crearAlmacen($sucursal, $almacenA, 'ALM-COM-B', 'Almacén Comisión B');
        $almacenExcluido = $this->crearAlmacen($sucursal, $almacenA, 'ALM-COM-X', 'Almacén No Incluido');
        $vendedor = $this->crearUsuario($sucursal, 'vendedor-reglas', 'Vendedor Reglas');
        $vendedorSinGrupo = $this->crearUsuario($sucursal, 'vendedor-sin-grupo', 'Vendedor Sin Grupo');
        $skus = ProductoSku::query()->with('producto')->get();
        $skuGrupo = $skus->firstOrFail();
        $skuSinGrupo = $skus->first(fn (ProductoSku $sku) => $sku->producto?->prd_lna_id !== $skuGrupo->producto?->prd_lna_id);
        $this->assertNotNull($skuSinGrupo, 'Los datos base deben contener una segunda línea para esta prueba.');
        $grupo = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
        $perfil = ComisionVendedor::query()->create([
            'cve_usr_id' => $vendedor->usr_id,
            'cve_cgr_id' => $grupo->cgr_id,
            'cve_numero' => 'QA-7',
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
        $periodo->almacenes()->attach([$almacenA->alm_id, $almacenB->alm_id]);
        DB::table('tbl_comision_periodo_lineas_cpl')->insert([
            'cpl_cpe_id' => $periodo->cpe_id,
            'cpl_cgr_id' => $grupo->cgr_id,
            'cpl_lna_id' => $skuGrupo->producto->prd_lna_id,
        ]);
        DB::table('tbl_comision_periodo_vendedores_cpv')->insert([
            'cpv_cpe_id' => $periodo->cpe_id,
            'cpv_cve_id' => $perfil->cve_id,
            'cpv_cgr_id' => $grupo->cgr_id,
            'cpv_numero_vendedor' => 'QA-7',
        ]);
        ComisionPeriodoGrupo::query()->create([
            'cpg_cpe_id' => $periodo->cpe_id,
            'cpg_cgr_id' => $grupo->cgr_id,
            'cpg_vendedores_promedio' => 2,
            'cpg_incremento_meta' => 0,
        ]);

        return compact('sucursal', 'admin', 'almacenA', 'almacenB', 'almacenExcluido', 'vendedor', 'vendedorSinGrupo', 'skuGrupo', 'skuSinGrupo', 'grupo', 'perfil', 'periodo') + [
            'almacen_a' => $almacenA,
            'almacen_b' => $almacenB,
            'almacen_excluido' => $almacenExcluido,
            'vendedor_sin_grupo' => $vendedorSinGrupo,
            'sku_grupo' => $skuGrupo,
            'sku_sin_grupo' => $skuSinGrupo,
        ];
    }

    private function crearAlmacen(Sucursal $sucursal, Almacen $base, string $clave, string $nombre): Almacen
    {
        return Almacen::query()->create([
            'alm_scl_id' => $sucursal->scl_id,
            'alm_tal_id' => $base->alm_tal_id,
            'alm_nombre' => $nombre,
            'alm_clave' => $clave,
            'alm_estatus' => 'activo',
        ]);
    }

    private function crearUsuario(Sucursal $sucursal, string $usuario, string $nombre): Usuario
    {
        $registro = Usuario::query()->create([
            'usr_usuario' => $usuario,
            'usr_nombre' => $nombre,
            'usr_password' => Hash::make('secret'),
            'usr_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $registro->usr_id,
            'usc_scl_id' => $sucursal->scl_id,
            'usc_es_predeterminada' => true,
            'usc_estatus' => 'activo',
        ]);

        return $registro;
    }

    private function crearVenta(
        array $escenario,
        Almacen $almacen,
        ?int $vendedorId,
        ProductoSku $sku,
        float $ventaBruta,
        float $descuentoLinea,
        float $descuentoGlobal,
        string $folio,
        string $estatus = 'cobrada',
        float $cantidad = 1,
    ): PosVentaDetalle {
        [$caja, $sesion] = $this->cajaPara($escenario, $almacen);
        $importePartida = $ventaBruta - $descuentoLinea;
        $venta = PosVenta::query()->create([
            'psv_folio' => $folio,
            'psv_cse_id' => $sesion->cse_id,
            'psv_caj_id' => $caja->caj_id,
            'psv_scl_id' => $escenario['sucursal']->scl_id,
            'psv_alm_id' => $almacen->alm_id,
            'psv_usr_id' => $escenario['admin']->usr_id,
            'psv_tipo_operacion' => 'venta',
            'psv_estatus' => $estatus,
            'psv_subtotal' => $importePartida,
            'psv_descuento' => $descuentoGlobal,
            'psv_total' => $importePartida - $descuentoGlobal,
            'psv_pagado' => $importePartida - $descuentoGlobal,
            'psv_cambio' => 0,
            'psv_fecha_cobro' => now(),
        ]);

        return PosVentaDetalle::query()->create([
            'pvd_psv_id' => $venta->psv_id,
            'pvd_psk_id' => $sku->psk_id,
            'pvd_cantidad' => $cantidad,
            'pvd_precio_unitario' => $ventaBruta / $cantidad,
            'pvd_descuento_porcentaje' => $ventaBruta > 0 ? ($descuentoLinea / $ventaBruta) * 100 : 0,
            'pvd_descuento_importe' => $descuentoLinea,
            'pvd_importe' => $importePartida,
            'pvd_usr_id' => $vendedorId,
        ]);
    }

    private function crearDevolucion(array $escenario, PosVentaDetalle $origen, float $cantidad, float $credito, string $folio): void
    {
        $almacen = Almacen::query()->findOrFail($origen->venta->psv_alm_id);
        [$caja, $sesion] = $this->cajaPara($escenario, $almacen);
        $cambio = PosVenta::query()->create([
            'psv_folio' => $folio,
            'psv_cse_id' => $sesion->cse_id,
            'psv_caj_id' => $caja->caj_id,
            'psv_scl_id' => $escenario['sucursal']->scl_id,
            'psv_alm_id' => $almacen->alm_id,
            'psv_usr_id' => $escenario['admin']->usr_id,
            'psv_tipo_operacion' => 'cambio',
            'psv_venta_origen_id' => $origen->pvd_psv_id,
            'psv_estatus' => 'cobrada',
            'psv_subtotal' => 0,
            'psv_descuento' => 0,
            'psv_credito_cambio' => $credito,
            'psv_total' => 0,
            'psv_pagado' => 0,
            'psv_cambio' => 0,
            'psv_fecha_cobro' => now(),
        ]);
        PosCambioDetalle::query()->create([
            'pcd_psv_id' => $cambio->psv_id,
            'pcd_psv_origen_id' => $origen->pvd_psv_id,
            'pcd_pvd_origen_id' => $origen->pvd_id,
            'pcd_psk_id' => $origen->pvd_psk_id,
            'pcd_alm_id' => $almacen->alm_id,
            'pcd_cantidad' => $cantidad,
            'pcd_precio_unitario' => $credito / $cantidad,
            'pcd_importe_credito' => $credito,
            'pcd_condicion' => 'reventa',
            'pcd_created_by_usr_id' => $escenario['admin']->usr_id,
            'pcd_updated_by_usr_id' => $escenario['admin']->usr_id,
        ]);
    }

    private function cajaPara(array $escenario, Almacen $almacen): array
    {
        $caja = Caja::query()->firstOrCreate(
            ['caj_scl_id' => $escenario['sucursal']->scl_id, 'caj_alm_id' => $almacen->alm_id],
            ['caj_nombre' => 'Caja '.$almacen->alm_clave, 'caj_clave' => 'CAJ-'.$almacen->alm_id, 'caj_estatus' => 'activo'],
        );
        $sesion = CajaSesion::query()->firstOrCreate(
            ['cse_caj_id' => $caja->caj_id, 'cse_estatus' => 'activa'],
            [
                'cse_scl_id' => $escenario['sucursal']->scl_id,
                'cse_usr_apertura_id' => $escenario['admin']->usr_id,
                'cse_monto_apertura' => 0,
                'cse_abierta_at' => now(),
            ],
        );

        return [$caja, $sesion];
    }
}
