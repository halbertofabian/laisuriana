<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ComisionAjusteVendedor;
use App\Models\ComisionGrupo;
use App\Models\ComisionPeriodo;
use App\Models\ComisionPeriodoGrupo;
use App\Models\ComisionResultado;
use App\Models\ComisionVendedor;
use App\Models\Linea;
use App\Models\PosCambioDetalle;
use App\Models\PosVenta;
use App\Models\PosVentaDetalle;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use App\Services\Reportes\ComisionCalculoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ComisionesDemoSeeder extends Seeder
{
    private const ALMACEN_I_SURIANA = 'I. Suriana';

    private const ALMACEN_LA_I_SURIANA = 'La I. Suriana';

    private const FOLIO_PREFIJO = 'DEMO-COM';

    private const LINEAS_ROPA = [
        'LNA_CABALLERO',
        'LNA_ROPA_DE_BEBE',
        'LNA_ROPA_DE_NINO',
        'LNA_ROPA_DE_NINA',
    ];

    private const LINEAS_TELAS = [
        'LNA_TELAS',
        'LNA_BLANCOS',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = Usuario::query()->where('usr_usuario', 'admin')->first()
                ?? throw new RuntimeException('No se encontró el usuario administrador para crear el escenario demo.');
            [$sucursal, $almacenA, $almacenB] = $this->resolverOperacionExistente();
            [$lineasRopa, $lineasTelas, $skuRopa, $skuTelas] = $this->resolverCatalogoExistente();
            $ropa = ComisionGrupo::query()->where('cgr_clave', 'ROPA')->firstOrFail();
            $telas = ComisionGrupo::query()->where('cgr_clave', 'TELAS')->firstOrFail();
            $periodoFecha = $this->resolverPeriodoDemo();

            [$cajaA, $sesionA] = $this->guardarCajaSesion($sucursal, $almacenA, 'DEMO-CAJA-A', 'Caja Demo A', $admin, $periodoFecha);
            [$cajaB, $sesionB] = $this->guardarCajaSesion($sucursal, $almacenB, 'DEMO-CAJA-B', 'Caja Demo B', $admin, $periodoFecha);

            $vendedores = collect([
                ['usuario' => 'demo.rosario', 'nombre' => 'Rosario', 'numero' => 'D-05', 'grupo' => $ropa, 'tasa' => null, 'ajuste' => 0, 'motivo' => null],
                ['usuario' => 'demo.miranda', 'nombre' => 'Miranda', 'numero' => 'D-15', 'grupo' => $ropa, 'tasa' => null, 'ajuste' => 0, 'motivo' => null],
                ['usuario' => 'demo.lilia', 'nombre' => 'Lilia', 'numero' => 'D-18', 'grupo' => $ropa, 'tasa' => 0.7, 'ajuste' => -0.2, 'motivo' => 'Ajuste por desempeño: -0.2 puntos porcentuales.'],
                ['usuario' => 'demo.alejandro', 'nombre' => 'Alejandro', 'numero' => 'D-10', 'grupo' => $telas, 'tasa' => 1.0, 'ajuste' => 0.1, 'motivo' => 'Ajuste por buen desempeño: +0.1 puntos porcentuales.'],
            ])->map(function (array $fila) use ($sucursal, $admin): array {
                $usuario = Usuario::query()->updateOrCreate(
                    ['usr_usuario' => $fila['usuario']],
                    [
                        'usr_nombre' => $fila['nombre'],
                        'usr_password' => Hash::make(str()->random(64)),
                        'usr_estatus' => 'activo',
                        'usr_deleted' => false,
                        'usr_deleted_at' => null,
                    ],
                );
                $this->relacionarUsuarioSucursal($usuario, $sucursal, true);
                $perfil = ComisionVendedor::query()->updateOrCreate(
                    ['cve_usr_id' => $usuario->usr_id],
                    [
                        'cve_cgr_id' => $fila['grupo']->cgr_id,
                        'cve_numero' => $fila['numero'],
                        'cve_estatus' => 'activo',
                        'cve_updated_by_usr_id' => $admin->usr_id,
                    ],
                );

                return [...$fila, 'registro' => $usuario, 'perfil' => $perfil];
            })->keyBy('usuario');

            $datosPeriodo = [
                'cpe_factor_comisionable' => 33,
                'cpe_tasa_general' => 0.9,
                'cpe_cumplimiento_minimo' => 100,
                'cpe_estatus' => 'borrador',
                'cpe_calculado_at' => null,
                'cpe_calculado_by_usr_id' => null,
                'cpe_cerrado_at' => null,
                'cpe_cerrado_by_usr_id' => null,
                'cpe_updated_by_usr_id' => $admin->usr_id,
            ];
            $periodo = ComisionPeriodo::query()
                ->where('cpe_scl_id', $sucursal->scl_id)
                ->whereDate('cpe_periodo', $periodoFecha->toDateString())
                ->first();
            if ($periodo) {
                $periodo->update($datosPeriodo);
            } else {
                $periodo = ComisionPeriodo::query()->create([
                    'cpe_scl_id' => $sucursal->scl_id,
                    'cpe_periodo' => $periodoFecha->toDateString(),
                    ...$datosPeriodo,
                ]);
            }
            ComisionResultado::query()->where('crs_cpe_id', $periodo->cpe_id)->delete();
            $periodo->almacenes()->sync([$almacenA->alm_id, $almacenB->alm_id]);

            DB::table('tbl_comision_periodo_lineas_cpl')->where('cpl_cpe_id', $periodo->cpe_id)->delete();
            $filasLineas = $lineasRopa
                ->map(fn (Linea $linea): array => $this->filaLineaPeriodo($periodo, $ropa, $linea))
                ->merge($lineasTelas->map(fn (Linea $linea): array => $this->filaLineaPeriodo($periodo, $telas, $linea)))
                ->values()
                ->all();
            DB::table('tbl_comision_periodo_lineas_cpl')->insert($filasLineas);

            ComisionPeriodoGrupo::query()->updateOrCreate(
                ['cpg_cpe_id' => $periodo->cpe_id, 'cpg_cgr_id' => $ropa->cgr_id],
                ['cpg_vendedores_promedio' => 4, 'cpg_incremento_meta' => 9.32],
            );
            ComisionPeriodoGrupo::query()->updateOrCreate(
                ['cpg_cpe_id' => $periodo->cpe_id, 'cpg_cgr_id' => $telas->cgr_id],
                ['cpg_vendedores_promedio' => 1.32, 'cpg_incremento_meta' => 5.57],
            );

            DB::table('tbl_comision_periodo_vendedores_cpv')->where('cpv_cpe_id', $periodo->cpe_id)->delete();
            foreach ($vendedores as $fila) {
                DB::table('tbl_comision_periodo_vendedores_cpv')->insert([
                    'cpv_cpe_id' => $periodo->cpe_id,
                    'cpv_cve_id' => $fila['perfil']->cve_id,
                    'cpv_cgr_id' => $fila['grupo']->cgr_id,
                    'cpv_numero_vendedor' => $fila['numero'],
                    'cpv_created_at' => now(),
                    'cpv_updated_at' => now(),
                ]);
                ComisionAjusteVendedor::query()->updateOrCreate(
                    ['cav_cpe_id' => $periodo->cpe_id, 'cav_cve_id' => $fila['perfil']->cve_id],
                    [
                        'cav_ajuste_tasa' => $fila['ajuste'],
                        'cav_tasa_final' => $fila['tasa'],
                        'cav_bono' => 0,
                        'cav_motivo' => $fila['motivo'],
                    ],
                );
            }

            $fecha = $periodoFecha->copy()->addDays(4)->setTime(12, 0);
            $rosarioA = $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuRopa, $vendedores['demo.rosario']['registro'], 300000, 500, 0, 'R05-A', $fecha);
            $rosarioB = $this->guardarVenta($sucursal, $almacenB, $cajaB, $sesionB, $admin, $skuRopa, $vendedores['demo.rosario']['registro'], 205909.13, 0, 0, 'R05-B', $fecha->copy()->addDay());
            $this->guardarDevolucion($sucursal, $almacenB, $cajaB, $sesionB, $admin, $rosarioB, 10000, 'R05-DEV', $fecha->copy()->addDays(2));

            $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuRopa, $vendedores['demo.miranda']['registro'], 201000, 0, 1000, 'M15-A', $fecha->copy()->addDays(3));
            $this->guardarVenta($sucursal, $almacenB, $cajaB, $sesionB, $admin, $skuRopa, $vendedores['demo.miranda']['registro'], 141219.72, 0, 0, 'M15-B', $fecha->copy()->addDays(4));
            $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuRopa, $vendedores['demo.lilia']['registro'], 250000, 0, 0, 'L18-A', $fecha->copy()->addDays(5));
            $this->guardarVenta($sucursal, $almacenB, $cajaB, $sesionB, $admin, $skuRopa, $vendedores['demo.lilia']['registro'], 123363.71, 0, 0, 'L18-B', $fecha->copy()->addDays(6));
            $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuTelas, $vendedores['demo.alejandro']['registro'], 140000, 0, 0, 'A10-A', $fecha->copy()->addDays(7));
            $this->guardarVenta($sucursal, $almacenB, $cajaB, $sesionB, $admin, $skuTelas, $vendedores['demo.alejandro']['registro'], 103000.85, 0, 0, 'A10-B', $fecha->copy()->addDays(8));

            $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuRopa, null, 25000, 0, 0, 'SIN-ATENCION-ROPA', $fecha->copy()->addDays(9));
            $this->guardarVenta($sucursal, $almacenB, $cajaB, $sesionB, $admin, $skuTelas, null, 10000, 0, 0, 'SIN-ATENCION-TELAS', $fecha->copy()->addDays(10));
            $this->guardarVenta($sucursal, $almacenA, $cajaA, $sesionA, $admin, $skuRopa, $vendedores['demo.rosario']['registro'], 50000, 0, 0, 'CANCELADA', $fecha->copy()->addDays(11), 'cancelada');

            app(ComisionCalculoService::class)->calcular($periodo->refresh(), (int) $admin->usr_id);
        });

        $this->mostrarResumen();
    }

    private function resolverOperacionExistente(): array
    {
        $almacenes = Almacen::query()
            ->with('sucursal')
            ->whereIn('alm_nombre', [self::ALMACEN_I_SURIANA, self::ALMACEN_LA_I_SURIANA])
            ->where('alm_estatus', 'activo')
            ->where('alm_deleted', false)
            ->get();

        $porNombre = $almacenes->keyBy('alm_nombre');
        if (
            $almacenes->count() !== 2
            || ! $porNombre->has(self::ALMACEN_I_SURIANA)
            || ! $porNombre->has(self::ALMACEN_LA_I_SURIANA)
        ) {
            throw new RuntimeException(
                'El seed requiere los almacenes activos existentes “'.self::ALMACEN_I_SURIANA.'” y “'.self::ALMACEN_LA_I_SURIANA.'”. No se creó ni modificó ningún almacén.',
            );
        }

        $sucursalIds = $almacenes->pluck('alm_scl_id')->unique()->values();
        if ($sucursalIds->count() !== 1) {
            throw new RuntimeException('Los almacenes I. Suriana y La I. Suriana deben pertenecer a la misma sucursal.');
        }

        $sucursal = Sucursal::query()
            ->whereKey($sucursalIds->first())
            ->where('scl_estatus', 'activo')
            ->where('scl_deleted', false)
            ->first();
        if (! $sucursal) {
            throw new RuntimeException('La sucursal de los almacenes I. Suriana y La I. Suriana no está activa.');
        }

        return [
            $sucursal,
            $porNombre->get(self::ALMACEN_I_SURIANA),
            $porNombre->get(self::ALMACEN_LA_I_SURIANA),
        ];
    }

    private function resolverCatalogoExistente(): array
    {
        $claves = [...self::LINEAS_ROPA, ...self::LINEAS_TELAS];
        $lineas = Linea::query()
            ->whereIn('lna_clave', $claves)
            ->where('lna_estatus', 'activo')
            ->where('lna_deleted', false)
            ->get()
            ->keyBy('lna_clave');
        $faltantes = collect($claves)->reject(fn (string $clave): bool => $lineas->has($clave))->values();
        if ($faltantes->isNotEmpty()) {
            throw new RuntimeException('Faltan líneas activas requeridas para comisiones: '.$faltantes->implode(', ').'.');
        }

        $lineasRopa = collect(self::LINEAS_ROPA)->map(fn (string $clave): Linea => $lineas->get($clave));
        $lineasTelas = collect(self::LINEAS_TELAS)->map(fn (string $clave): Linea => $lineas->get($clave));
        $skuRopa = $this->skuActivoDeLinea($lineas->get('LNA_CABALLERO'));
        $skuTelas = $this->skuActivoDeLinea($lineas->get('LNA_TELAS'));

        return [$lineasRopa, $lineasTelas, $skuRopa, $skuTelas];
    }

    private function skuActivoDeLinea(Linea $linea): ProductoSku
    {
        return ProductoSku::query()
            ->with('producto')
            ->where('psk_estatus', 'activo')
            ->where('psk_deleted', false)
            ->whereHas('producto', fn ($query) => $query
                ->where('prd_lna_id', $linea->lna_id)
                ->where('prd_estatus', 'activo')
                ->where('prd_deleted', false))
            ->orderBy('psk_id')
            ->first()
            ?? throw new RuntimeException("No existe un SKU activo para la línea {$linea->lna_nombre}.");
    }

    private function resolverPeriodoDemo(): Carbon
    {
        $ultimaVentaReal = PosVenta::query()
            ->where('psv_folio', 'not like', self::FOLIO_PREFIJO.'-%')
            ->whereNotNull('psv_fecha_cobro')
            ->max('psv_fecha_cobro');

        return $ultimaVentaReal
            ? Carbon::parse($ultimaVentaReal)->addMonthNoOverflow()->startOfMonth()
            : now()->startOfMonth();
    }

    private function filaLineaPeriodo(ComisionPeriodo $periodo, ComisionGrupo $grupo, Linea $linea): array
    {
        return [
            'cpl_cpe_id' => $periodo->cpe_id,
            'cpl_cgr_id' => $grupo->cgr_id,
            'cpl_lna_id' => $linea->lna_id,
            'cpl_created_at' => now(),
            'cpl_updated_at' => now(),
        ];
    }

    private function relacionarUsuarioSucursal(Usuario $usuario, Sucursal $sucursal, bool $predeterminada): void
    {
        UsuarioSucursal::query()->updateOrCreate(
            ['usc_usr_id' => $usuario->usr_id, 'usc_scl_id' => $sucursal->scl_id],
            [
                'usc_es_predeterminada' => $predeterminada,
                'usc_estatus' => 'activo',
                'usc_deleted' => false,
                'usc_deleted_at' => null,
            ],
        );
    }

    private function guardarCajaSesion(Sucursal $sucursal, Almacen $almacen, string $clave, string $nombre, Usuario $admin, Carbon $periodo): array
    {
        $caja = Caja::query()->updateOrCreate(
            ['caj_scl_id' => $sucursal->scl_id, 'caj_clave' => $clave],
            [
                'caj_nombre' => $nombre,
                'caj_alm_id' => $almacen->alm_id,
                'caj_estatus' => 'activo',
                'caj_deleted' => false,
                'caj_deleted_at' => null,
                'caj_updated_by_usr_id' => $admin->usr_id,
            ],
        );
        $sesion = CajaSesion::query()->updateOrCreate(
            ['cse_caj_id' => $caja->caj_id, 'cse_estatus' => 'activa'],
            [
                'cse_scl_id' => $sucursal->scl_id,
                'cse_usr_apertura_id' => $admin->usr_id,
                'cse_monto_apertura' => 0,
                'cse_abierta_at' => $periodo->copy()->addDays(2)->setTime(9, 0),
                'cse_cerrada_at' => null,
            ],
        );

        return [$caja, $sesion];
    }

    private function guardarVenta(
        Sucursal $sucursal,
        Almacen $almacen,
        Caja $caja,
        CajaSesion $sesion,
        Usuario $admin,
        ProductoSku $sku,
        ?Usuario $vendedor,
        float $importePartida,
        float $descuentoLinea,
        float $descuentoGlobal,
        string $sufijoFolio,
        Carbon $fecha,
        string $estatus = 'cobrada',
    ): PosVentaDetalle {
        $folio = self::FOLIO_PREFIJO.'-'.$fecha->format('Ym').'-'.$sufijoFolio;
        $venta = PosVenta::query()->updateOrCreate(
            ['psv_folio' => $folio],
            [
                'psv_cse_id' => $sesion->cse_id,
                'psv_caj_id' => $caja->caj_id,
                'psv_scl_id' => $sucursal->scl_id,
                'psv_alm_id' => $almacen->alm_id,
                'psv_usr_id' => $admin->usr_id,
                'psv_tipo_operacion' => 'venta',
                'psv_estatus' => $estatus,
                'psv_subtotal' => $importePartida,
                'psv_descuento' => $descuentoGlobal,
                'psv_total' => $importePartida - $descuentoGlobal,
                'psv_pagado' => $importePartida - $descuentoGlobal,
                'psv_cambio' => 0,
                'psv_notas' => 'Venta generada por ComisionesDemoSeeder.',
                'psv_fecha_cobro' => $fecha,
                'psv_updated_by_usr_id' => $admin->usr_id,
                'psv_deleted' => false,
                'psv_deleted_at' => null,
            ],
        );

        return PosVentaDetalle::query()->updateOrCreate(
            ['pvd_psv_id' => $venta->psv_id, 'pvd_psk_id' => $sku->psk_id],
            [
                'pvd_cantidad' => 1,
                'pvd_precio_unitario' => $importePartida + $descuentoLinea,
                'pvd_descuento_porcentaje' => $descuentoLinea > 0 ? round(($descuentoLinea / ($importePartida + $descuentoLinea)) * 100, 2) : 0,
                'pvd_descuento_importe' => $descuentoLinea,
                'pvd_importe' => $importePartida,
                'pvd_usr_id' => $vendedor?->usr_id,
                'pvd_updated_by_usr_id' => $admin->usr_id,
                'pvd_deleted' => false,
                'pvd_deleted_at' => null,
            ],
        );
    }

    private function guardarDevolucion(
        Sucursal $sucursal,
        Almacen $almacen,
        Caja $caja,
        CajaSesion $sesion,
        Usuario $admin,
        PosVentaDetalle $origen,
        float $importe,
        string $sufijoFolio,
        Carbon $fecha,
    ): void {
        $folio = self::FOLIO_PREFIJO.'-'.$fecha->format('Ym').'-'.$sufijoFolio;
        $cambio = PosVenta::query()->updateOrCreate(
            ['psv_folio' => $folio],
            [
                'psv_cse_id' => $sesion->cse_id,
                'psv_caj_id' => $caja->caj_id,
                'psv_scl_id' => $sucursal->scl_id,
                'psv_alm_id' => $almacen->alm_id,
                'psv_usr_id' => $admin->usr_id,
                'psv_tipo_operacion' => 'cambio',
                'psv_venta_origen_id' => $origen->pvd_psv_id,
                'psv_estatus' => 'cobrada',
                'psv_subtotal' => 0,
                'psv_descuento' => 0,
                'psv_credito_cambio' => $importe,
                'psv_total' => 0,
                'psv_pagado' => 0,
                'psv_cambio' => 0,
                'psv_notas' => 'Devolución generada por ComisionesDemoSeeder.',
                'psv_fecha_cobro' => $fecha,
                'psv_updated_by_usr_id' => $admin->usr_id,
                'psv_deleted' => false,
                'psv_deleted_at' => null,
            ],
        );
        PosCambioDetalle::query()->updateOrCreate(
            ['pcd_psv_id' => $cambio->psv_id, 'pcd_pvd_origen_id' => $origen->pvd_id],
            [
                'pcd_psv_origen_id' => $origen->pvd_psv_id,
                'pcd_psk_id' => $origen->pvd_psk_id,
                'pcd_alm_id' => $almacen->alm_id,
                'pcd_cantidad' => 1,
                'pcd_precio_unitario' => $importe,
                'pcd_importe_credito' => $importe,
                'pcd_condicion' => 'reventa',
                'pcd_updated_by_usr_id' => $admin->usr_id,
                'pcd_deleted' => false,
                'pcd_deleted_at' => null,
            ],
        );
    }

    private function mostrarResumen(): void
    {
        if (! $this->command) {
            return;
        }

        $periodo = ComisionPeriodo::query()
            ->whereHas('resultados')
            ->whereHas('almacenes', fn ($query) => $query->where('alm_nombre', self::ALMACEN_I_SURIANA))
            ->with('resultados')
            ->latest('cpe_id')
            ->firstOrFail();

        $this->command->newLine();
        $this->command->info('Escenario demo de comisiones creado para '.$periodo->cpe_periodo->format('Y-m').'.');
        $this->command->table(
            ['Vendedor', 'Ventas', 'Cumplimiento', 'Tasa', 'Comisión'],
            $periodo->resultados->map(fn (ComisionResultado $resultado) => [
                $resultado->crs_nombre_vendedor,
                '$'.number_format((float) $resultado->crs_ventas_totales, 2),
                number_format((float) $resultado->crs_cumplimiento, 2).'%',
                number_format((float) $resultado->crs_tasa_final, 2).'%',
                '$'.number_format((float) $resultado->crs_comision, 2),
            ])->all(),
        );
        $this->command->comment('Abre Casa Matriz → Reportes → Ventas → Comisiones por vendedor y selecciona el mes indicado.');
    }
}
