<?php

namespace Tests\Unit\Operacion;

use App\Models\EtiquetaFormato;
use App\Models\EtiquetaLineaConfiguracion;
use App\Models\EtiquetaPlantilla;
use App\Models\Producto;
use App\Models\ProductoSku;
use App\Models\RecepcionMercanciaDetalle;
use App\Services\AuditoriaService;
use App\Services\Operacion\Comercial\EtiquetaProductoRenderer;
use App\Services\Operacion\Comercial\EtiquetadoRecepcionService;
use Illuminate\Support\Collection;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class EtiquetadoRecepcionPdfTest extends TestCase
{
    public function test_distribuye_las_etiquetas_en_filas_y_columnas_de_una_hoja(): void
    {
        $formato = new EtiquetaFormato();
        $formato->forceFill([
            'etf_ancho_mm' => 50,
            'etf_alto_mm' => 30,
            'etf_tipo_salida' => 'hoja',
            'etf_columnas' => 2,
            'etf_filas' => 2,
            'etf_separacion_h_mm' => 3,
            'etf_separacion_v_mm' => 3,
            'etf_margen_izq_mm' => 2,
            'etf_margen_der_mm' => 2,
            'etf_margen_sup_mm' => 2,
            'etf_margen_inf_mm' => 2,
        ]);

        $plantilla = new EtiquetaPlantilla();
        $plantilla->forceFill(['etp_campos' => ['nombre_producto', 'sku', 'codigo_barras', 'precio']]);

        $configuracion = new EtiquetaLineaConfiguracion();
        $configuracion->forceFill(['elc_etp_id' => 7]);
        $configuracion->setRelation('plantilla', $plantilla);

        $producto = new Producto();
        $producto->forceFill(['prd_nombre' => 'Playera de prueba']);
        $producto->setRelation('linea', null);
        $producto->setRelation('unidad', null);

        $sku = new ProductoSku();
        $sku->forceFill([
            'psk_nombre' => 'Playera azul CH',
            'psk_codigo' => 'SKU-PRUEBA-CH',
            'psk_codigo_barras' => '7501234567890',
            'psk_precio' => 199.90,
        ]);
        $sku->setRelation('producto', $producto);
        $sku->setRelation('valoresAtributo', new Collection());

        $detalle = new RecepcionMercanciaDetalle();
        $detalle->forceFill(['rmd_cantidad' => 5]);

        $item = compact('producto', 'sku', 'detalle', 'configuracion');
        $item['config'] = $item['configuracion'];
        unset($item['configuracion']);
        $item['etiquetas'] = 5;

        $service = new EtiquetadoRecepcionService(
            Mockery::mock(AuditoriaService::class),
            new EtiquetaProductoRenderer(),
        );
        $nuevoPdf = new ReflectionMethod($service, 'nuevoPdf');
        $agregarGrupo = new ReflectionMethod($service, 'agregarGrupo');
        $pdf = $nuevoPdf->invoke($service, $formato);

        $agregarGrupo->invoke($service, $pdf, [
            'formato' => $formato,
            'plantilla' => $plantilla,
            'items' => [$item],
        ]);

        $this->assertSame(2, $pdf->getNumPages());
        $dimensiones = $pdf->getPageDimensions(1);
        $this->assertEqualsWithDelta(103, $dimensiones['wk'], 0.01);
        $this->assertEqualsWithDelta(63, $dimensiones['hk'], 0.01);

        $formatoTermicoA = new EtiquetaFormato();
        $formatoTermicoA->forceFill(array_merge($formato->getAttributes(), [
            'etf_tipo_salida' => 'termica', 'etf_ancho_mm' => 50, 'etf_alto_mm' => 30,
        ]));
        $formatoTermicoB = new EtiquetaFormato();
        $formatoTermicoB->forceFill(array_merge($formato->getAttributes(), [
            'etf_tipo_salida' => 'termica', 'etf_ancho_mm' => 80, 'etf_alto_mm' => 40,
        ]));
        $item['etiquetas'] = 1;
        $pdfMixto = $nuevoPdf->invoke($service, $formatoTermicoA);
        $agregarGrupo->invoke($service, $pdfMixto, ['formato' => $formatoTermicoA, 'plantilla' => $plantilla, 'items' => [$item]]);
        $agregarGrupo->invoke($service, $pdfMixto, ['formato' => $formatoTermicoB, 'plantilla' => $plantilla, 'items' => [$item]]);

        $this->assertSame(2, $pdfMixto->getNumPages());
        $this->assertEqualsWithDelta(50, $pdfMixto->getPageDimensions(1)['wk'], 0.01);
        $this->assertEqualsWithDelta(30, $pdfMixto->getPageDimensions(1)['hk'], 0.01);
        $this->assertEqualsWithDelta(80, $pdfMixto->getPageDimensions(2)['wk'], 0.01);
        $this->assertEqualsWithDelta(40, $pdfMixto->getPageDimensions(2)['hk'], 0.01);

        $contenido = $pdfMixto->Output('', 'S');
        $this->assertStringContainsString('SKU-PRUEBA-CH', $contenido);
        $this->assertStringContainsString('Playera azul CH', $contenido);
        $this->assertStringContainsString('$199.90', $contenido);
        $this->assertStringNotContainsString('NOMBRE PRODUCTO:', $contenido);
        $this->assertStringNotContainsString('PRECIO:', $contenido);
    }
}
