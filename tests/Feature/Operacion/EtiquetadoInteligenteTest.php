<?php

namespace Tests\Feature\Operacion;

use App\Models\Linea;
use App\Models\UnidadMedida;
use App\Models\Usuario;
use Database\Seeders\SeguridadBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtiquetadoInteligenteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadBaseSeeder::class);
        $this->actingAs(Usuario::query()->where('usr_usuario', 'admin')->firstOrFail());
    }

    public function test_configura_formato_plantilla_linea_y_regla_de_unidad(): void
    {
        $linea = Linea::query()->create(['lna_nombre' => 'Ropa bebé', 'lna_clave' => 'LNA_BEBE', 'lna_estatus' => 'activo']);
        $unidad = UnidadMedida::query()->create(['umd_nombre' => 'Pieza', 'umd_codigo' => 'PZA', 'umd_clave' => 'UMD_PZA', 'umd_tipo_cantidad' => 'entero', 'umd_estatus' => 'activo']);

        $this->get(route('desktop.operacion.etiquetas.index'))
            ->assertOk()
            ->assertSee('Define una vez las reglas')
            ->assertSee('Crear primer formato');

        $formato = $this->postJson(route('desktop.operacion.etiquetas.formatos.store'), [
            'etf_nombre' => 'Etiqueta ropa pequeña', 'etf_descripcion' => 'Ropa de bebé',
            'etf_ancho_mm' => 50, 'etf_alto_mm' => 30, 'etf_orientacion' => 'auto',
            'etf_tipo_salida' => 'termica', 'etf_estatus' => 'activo',
        ])->assertOk()->json('data.etf_id');

        $plantilla = $this->postJson(route('desktop.operacion.etiquetas.plantillas.store'), [
            'etp_nombre' => 'Contenido ropa', 'etp_descripcion' => 'Nombre, talla y precio',
            'etp_campos' => ['nombre_producto', 'codigo_barras', 'talla', 'precio'], 'etp_estatus' => 'activo',
        ])->assertOk()->json('data.etp_id');

        $this->putJson(route('desktop.operacion.etiquetas.plantillas.update', $plantilla), [
            'etp_nombre' => 'Contenido ropa actualizado', 'etp_descripcion' => 'Nombre, talla, color y precio',
            'etp_campos' => ['nombre_producto', 'codigo_barras', 'talla', 'color', 'precio'], 'etp_estatus' => 'activo',
        ])->assertOk()->assertJsonPath('data.etp_nombre', 'Contenido ropa actualizado');

        $this->postJson(route('desktop.operacion.etiquetas.asignaciones.store'), [
            'elc_lna_id' => $linea->lna_id, 'elc_etf_id' => $formato,
            'elc_etp_id' => $plantilla, 'elc_estatus' => 'activo',
        ])->assertOk();

        $this->postJson(route('desktop.operacion.etiquetas.reglas.store'), [
            'eur_umd_id' => $unidad->umd_id, 'eur_regla' => 'por_unidad_recibida',
        ])->assertOk();

        $this->assertDatabaseHas('tbl_etiqueta_linea_config_elc', ['elc_lna_id' => $linea->lna_id, 'elc_etf_id' => $formato, 'elc_etp_id' => $plantilla]);
        $this->assertDatabaseHas('tbl_etiqueta_unidad_reglas_eur', ['eur_umd_id' => $unidad->umd_id, 'eur_regla' => 'por_unidad_recibida']);
    }
}
