<?php

namespace Tests\Unit\Reportes;

use App\Services\Reportes\ReporteConsultaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReporteCatalogoTest extends TestCase
{
    public function test_catalogo_expone_reportes_individuales_organizados(): void
    {
        $catalogo = (new ReporteConsultaService())->catalogo();

        $this->assertSame(['ventas', 'caja', 'inventario', 'compras'], array_keys($catalogo));
        $this->assertCount(16, collect($catalogo)->flatMap(fn (array $grupo) => $grupo['reportes']));
        $this->assertSame(
            collect($catalogo)->flatMap(fn (array $grupo) => $grupo['reportes'])->pluck('slug')->unique()->count(),
            collect($catalogo)->flatMap(fn (array $grupo) => $grupo['reportes'])->count(),
        );
    }

    #[DataProvider('reportesCriticos')]
    public function test_reportes_criticos_tienen_definicion_y_permiso(string $slug, string $permiso): void
    {
        $definicion = (new ReporteConsultaService())->definicion($slug);

        $this->assertSame($slug, $definicion['slug']);
        $this->assertSame($permiso, $definicion['permiso']);
        $this->assertNotEmpty($definicion['titulo']);
        $this->assertNotEmpty($definicion['descripcion']);
    }

    public static function reportesCriticos(): array
    {
        return [
            ['ventas-vendedor', 'reportes.ventas.ver'],
            ['ventas-categoria', 'reportes.ventas.ver'],
            ['ventas-devoluciones', 'reportes.ventas.ver'],
            ['caja-cortes', 'reportes.caja.ver'],
            ['caja-retiros', 'reportes.caja.ver'],
            ['inventario-movimientos', 'reportes.inventario.ver'],
            ['compras-proveedor', 'reportes.inventario.ver'],
        ];
    }
}
