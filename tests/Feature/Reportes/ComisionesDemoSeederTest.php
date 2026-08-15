<?php

namespace Tests\Feature\Reportes;

use App\Models\Almacen;
use App\Models\ComisionPeriodo;
use App\Models\ComisionResultado;
use App\Models\Linea;
use App\Models\PosVenta;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use App\Models\UsuarioSucursal;
use Database\Seeders\ComisionesDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComisionesDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_detiene_sin_crear_sucursales_ni_almacenes_si_faltan_los_requeridos(): void
    {
        $this->seed(DatabaseSeeder::class);
        $totalSucursales = Sucursal::query()->count();
        $totalAlmacenes = Almacen::query()->withDeleted()->count();

        try {
            $this->seed(ComisionesDemoSeeder::class);
            $this->fail('El seed debía detenerse cuando faltan los almacenes reales.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('No se creó ni modificó ningún almacén', $exception->getMessage());
        }

        $this->assertSame($totalSucursales, Sucursal::query()->count());
        $this->assertSame($totalAlmacenes, Almacen::query()->withDeleted()->count());
        $this->assertFalse(Usuario::query()->where('usr_usuario', 'demo.rosario')->exists());
    }

    public function test_reutiliza_la_sucursal_y_los_almacenes_existentes_sin_crearlos(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$matriz] = $this->prepararCatalogosExistentes();
        $totalSucursales = Sucursal::query()->count();
        $totalAlmacenes = Almacen::query()->withDeleted()->count();

        $this->seed(ComisionesDemoSeeder::class);
        $this->seed(ComisionesDemoSeeder::class);

        $periodo = ComisionPeriodo::query()
            ->where('cpe_scl_id', $matriz->scl_id)
            ->firstOrFail();
        $resultados = ComisionResultado::query()
            ->where('crs_cpe_id', $periodo->cpe_id)
            ->get()
            ->keyBy('crs_nombre_vendedor');

        $this->assertSame('calculado', $periodo->cpe_estatus);
        $this->assertCount(4, $resultados);
        $this->assertSame('495909.13', $resultados['Rosario']->crs_ventas_totales);
        $this->assertSame('149.90', $resultados['Rosario']->crs_cumplimiento);
        $this->assertSame('1472.85', $resultados['Rosario']->crs_comision);
        $this->assertSame('341219.72', $resultados['Miranda']->crs_ventas_totales);
        $this->assertSame('1013.42', $resultados['Miranda']->crs_comision);
        $this->assertSame('0.7000', $resultados['Lilia']->crs_tasa_final);
        $this->assertSame('862.47', $resultados['Lilia']->crs_comision);
        $this->assertSame('125.04', $resultados['Alejandro']->crs_cumplimiento);
        $this->assertSame('1.0000', $resultados['Alejandro']->crs_tasa_final);
        $this->assertSame('801.90', $resultados['Alejandro']->crs_comision);
        $this->assertSame(12, PosVenta::query()->where('psv_folio', 'like', 'DEMO-COM-%')->count());
        $this->assertSame($totalSucursales, Sucursal::query()->count());
        $this->assertSame($totalAlmacenes, Almacen::query()->withDeleted()->count());
        $this->assertFalse(Sucursal::query()->where('scl_clave', 'DEMO-COMISIONES')->exists());
        $this->assertEqualsCanonicalizing(
            ['I. Suriana', 'La I. Suriana'],
            $periodo->almacenes()->pluck('alm_nombre')->all(),
        );
    }

    public function test_el_listado_de_ventas_muestra_al_vendedor_de_la_partida_y_no_al_cajero(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$matriz] = $this->prepararCatalogosExistentes();
        $this->seed(ComisionesDemoSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $periodo = ComisionPeriodo::query()->where('cpe_scl_id', $matriz->scl_id)->firstOrFail();
        $filtros = [
            'fecha_desde' => $periodo->cpe_periodo->copy()->startOfMonth()->toDateString(),
            'fecha_hasta' => $periodo->cpe_periodo->copy()->endOfMonth()->toDateString(),
        ];
        $prefijo = 'DEMO-COM-'.$periodo->cpe_periodo->format('Ym').'-';

        $response = $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $matriz->scl_id])
            ->getJson(route('desktop.ventas.data', $filtros))
            ->assertOk();
        $ventas = collect($response->json('data'))->keyBy('psv_folio');

        $this->assertSame('Rosario', $ventas[$prefijo.'R05-A']['vendedor']);
        $this->assertSame('Miranda', $ventas[$prefijo.'M15-A']['vendedor']);
        $this->assertSame('Lilia', $ventas[$prefijo.'L18-A']['vendedor']);
        $this->assertSame('Alejandro', $ventas[$prefijo.'A10-A']['vendedor']);
        $this->assertSame('Rosario', $ventas[$prefijo.'R05-DEV']['vendedor']);
        $this->assertSame('Sin atención', $ventas[$prefijo.'SIN-ATENCION-ROPA']['vendedor']);
        $this->assertNotSame('Administrador General', $ventas[$prefijo.'R05-A']['vendedor']);

        $busqueda = $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $matriz->scl_id])
            ->getJson(route('desktop.ventas.data', [...$filtros, 'buscar' => 'Rosario']))
            ->assertOk();
        $this->assertEqualsCanonicalizing(
            [
                $prefijo.'R05-A',
                $prefijo.'R05-B',
                $prefijo.'R05-DEV',
                $prefijo.'CANCELADA',
            ],
            collect($busqueda->json('data'))->pluck('psv_folio')->all(),
        );

        $listadoOperativo = $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $matriz->scl_id])
            ->getJson(route('operacion.ventas.data', $filtros))
            ->assertOk();
        $this->assertSame(
            'Alejandro',
            collect($listadoOperativo->json('data'))->firstWhere('psv_folio', $prefijo.'A10-A')['vendedor'],
        );
    }

    public function test_permite_filtrar_y_exportar_una_sucursal_asignada_pero_no_otra(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$matriz] = $this->prepararCatalogosExistentes();
        $this->seed(ComisionesDemoSeeder::class);
        $admin = Usuario::query()->where('usr_usuario', 'admin')->firstOrFail();
        $otraAsignada = Sucursal::query()->create([
            'scl_clave' => 'OTRA-ASIGNADA-COMISION',
            'scl_nombre' => 'Otra sucursal asignada',
            'scl_estatus' => 'activo',
        ]);
        UsuarioSucursal::query()->create([
            'usc_usr_id' => $admin->usr_id,
            'usc_scl_id' => $otraAsignada->scl_id,
            'usc_es_predeterminada' => false,
            'usc_estatus' => 'activo',
            'usc_deleted' => false,
        ]);
        $sinAcceso = Sucursal::query()->create([
            'scl_clave' => 'SIN-ACCESO-COMISION',
            'scl_nombre' => 'Sucursal sin acceso',
            'scl_estatus' => 'activo',
        ]);
        $filtros = [
            'sucursal_id' => $matriz->scl_id,
            'desde' => ComisionPeriodo::query()->where('cpe_scl_id', $matriz->scl_id)->firstOrFail()->cpe_periodo->startOfMonth()->toDateString(),
            'hasta' => ComisionPeriodo::query()->where('cpe_scl_id', $matriz->scl_id)->firstOrFail()->cpe_periodo->endOfMonth()->toDateString(),
        ];

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->get(route('reportes.show', ['reporte' => 'ventas-comisiones', 'sucursal_id' => $matriz->scl_id]))
            ->assertOk()
            ->assertSee('filter-commission-branch', false)
            ->assertSee('Casa Matriz')
            ->assertSee('Rosario');

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->get(route('desktop.operacion.gestion_configuraciones.comisiones.index', [
                'sucursal_id' => $matriz->scl_id,
                'periodo' => substr($filtros['desde'], 0, 7),
            ]))
            ->assertOk()
            ->assertSee('commission-branch-picker', false)
            ->assertSee('Casa Matriz')
            ->assertSee('Vista de consulta:')
            ->assertSee('Rosario')
            ->assertDontSee('form="commission-config-form"', false)
            ->assertDontSee('commission-calculate-form', false)
            ->assertDontSee('commission-close-form', false);

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->getJson(route('reportes.data', ['reporte' => 'ventas-comisiones', ...$filtros]))
            ->assertOk()
            ->assertJsonPath('sucursal_id', $matriz->scl_id)
            ->assertJsonPath('sucursal', 'Casa Matriz')
            ->assertJsonPath('total_registros', 4);

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->get(route('reportes.exportar', ['reporte' => 'ventas-comisiones', 'formato' => 'csv', ...$filtros]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->getJson(route('reportes.data', [
                'reporte' => 'ventas-comisiones',
                'sucursal_id' => $sinAcceso->scl_id,
                'desde' => now()->startOfMonth()->toDateString(),
            ]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['sucursal_activa_id' => $otraAsignada->scl_id])
            ->get(route('desktop.operacion.gestion_configuraciones.comisiones.index', [
                'sucursal_id' => $sinAcceso->scl_id,
            ]))
            ->assertForbidden();
    }

    private function prepararCatalogosExistentes(): array
    {
        $matriz = Sucursal::query()->where('scl_clave', 'MATRIZ')->firstOrFail();
        $tipo = TipoAlmacen::query()->where('tal_clave', 'principal')->firstOrFail();
        foreach ([
            ['clave' => 'ALM_1_VENTAS', 'nombre' => 'La I. Suriana'],
            ['clave' => 'ALM_1_I_SURIANA', 'nombre' => 'I. Suriana'],
        ] as $almacen) {
            Almacen::query()->create([
                'alm_scl_id' => $matriz->scl_id,
                'alm_tal_id' => $tipo->tal_id,
                'alm_clave' => $almacen['clave'],
                'alm_nombre' => $almacen['nombre'],
                'alm_estatus' => 'activo',
            ]);
        }

        $lineas = collect([
            'LNA_CABALLERO' => 'ROPA DE CABALLERO',
            'LNA_ROPA_DE_BEBE' => 'ROPA DE BEBE',
            'LNA_ROPA_DE_NINO' => 'ROPA DE NIÑO',
            'LNA_ROPA_DE_NINA' => 'ROPA DE NIÑA',
            'LNA_TELAS' => 'TELAS',
            'LNA_BLANCOS' => 'BLANCOS',
        ])->mapWithKeys(function (string $nombre, string $clave): array {
            $linea = Linea::query()->updateOrCreate(
                ['lna_clave' => $clave],
                ['lna_nombre' => $nombre, 'lna_estatus' => 'activo'],
            );

            return [$clave => $linea];
        });

        $productos = Producto::query()->orderBy('prd_id')->limit(2)->get();
        $productos->get(0)->update(['prd_lna_id' => $lineas->get('LNA_CABALLERO')->lna_id]);
        $productos->get(1)->update(['prd_lna_id' => $lineas->get('LNA_TELAS')->lna_id]);

        return [$matriz];
    }
}
