<?php

namespace Tests\Feature\Operacion;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaSesion;
use App\Models\ExistenciaAlmacen;
use App\Models\Facturacion;
use App\Models\PosVenta;
use App\Models\ProductoSku;
use App\Models\Sucursal;
use App\Models\TipoAlmacen;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturacionTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Almacen $origen;

    private Almacen $destino;

    private ProductoSku $sku;

    private PosVenta $venta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = Usuario::where('usr_usuario', 'admin')->firstOrFail();
        $sucursal = Sucursal::firstOrFail();
        foreach (['origen' => false, 'destino' => true] as $nombre => $habilitado) {
            $this->{$nombre} = Almacen::create([
                'alm_scl_id' => $sucursal->scl_id, 'alm_tal_id' => TipoAlmacen::firstOrFail()->tal_id,
                'alm_nombre' => $nombre, 'alm_clave' => 'FAC-'.$nombre, 'alm_estatus' => 'activo',
                'alm_permite_facturar' => $habilitado,
            ]);
        }
        $caja = Caja::create(['caj_scl_id' => $sucursal->scl_id, 'caj_alm_id' => $this->origen->alm_id, 'caj_nombre' => 'Facturación', 'caj_clave' => 'FAC', 'caj_estatus' => 'activo']);
        $sesion = CajaSesion::create(['cse_caj_id' => $caja->caj_id, 'cse_scl_id' => $sucursal->scl_id, 'cse_usr_apertura_id' => $this->admin->usr_id, 'cse_abierta_at' => now(), 'cse_estatus' => 'activa']);
        $this->sku = ProductoSku::where('psk_codigo', 'SKU-POLO-CH-AZM')->firstOrFail();
        $this->sku->producto->almacenesPermitidos()->sync([$this->destino->alm_id]);
        $this->venta = PosVenta::create([
            'psv_folio' => 'FAC-TEST', 'psv_cse_id' => $sesion->cse_id, 'psv_caj_id' => $caja->caj_id,
            'psv_scl_id' => $sucursal->scl_id, 'psv_alm_id' => $this->origen->alm_id,
            'psv_usr_id' => $this->admin->usr_id, 'psv_tipo_operacion' => 'venta', 'psv_estatus' => 'cobrada',
            'psv_subtotal' => 200, 'psv_total' => 200, 'psv_fecha_cobro' => now(),
        ]);
        foreach ([$this->origen, $this->destino] as $almacen) {
            $this->venta->detalle()->create(['pvd_psk_id' => $this->sku->psk_id, 'pvd_alm_id' => $almacen->alm_id, 'pvd_cantidad' => 1, 'pvd_precio_unitario' => 100, 'pvd_importe' => 100]);
            ExistenciaAlmacen::create(['exa_psk_id' => $this->sku->psk_id, 'exa_scl_id' => $sucursal->scl_id, 'exa_alm_id' => $almacen->alm_id, 'exa_existencia' => 10, 'exa_estatus' => 'activo']);
        }
        $this->actingAs($this->admin);
    }

    private function ajuste(array $extra = []): array
    {
        return array_replace([
            'almacen_id' => $this->destino->alm_id, 'version' => 0,
            'items' => [['psk_id' => $this->sku->psk_id, 'cantidad' => 2, 'precio' => 125]],
            'notas' => 'Diferencia permitida',
        ], $extra);
    }

    private function ruta(string $accion): string
    {
        return route('desktop.facturacion.'.$accion, $this->venta->psv_id);
    }

    public function test_venta_mixta_requiere_ajuste_y_guarda_diferencia_sin_mover_inventario(): void
    {
        $otroProducto = ProductoSku::where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();
        $this->venta->detalle()->where('pvd_alm_id', $this->origen->alm_id)->update(['pvd_psk_id' => $otroProducto->psk_id]);
        $stock = DB::table('tbl_existencias_almacen_exa')->get()->toJson();
        $movimientos = DB::table('tbl_movimientos_inventario_min')->count();
        $original = $this->venta->detalle()->get()->toJson();
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertUnprocessable()->assertJsonValidationErrors('venta');
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertOk()->assertJsonPath('data.fac_total', '250.00')->assertJsonPath('data.fac_diferencia', '50.00');
        $emitir = $this->postJson($this->ruta('emitir'), ['version' => 1])->assertOk()->assertJsonPath('data.fac_estado', 'simulada');
        $this->postJson($this->ruta('emitir'), ['version' => 1])->assertOk()->assertJsonPath('data.fac_folio', $emitir->json('data.fac_folio'));
        $this->assertDatabaseCount('tbl_facturacion_fac', 1);
        $this->assertSame($stock, DB::table('tbl_existencias_almacen_exa')->get()->toJson());
        $this->assertSame($movimientos, DB::table('tbl_movimientos_inventario_min')->count());
        $this->assertSame($original, $this->venta->detalle()->get()->toJson());
        $this->assertEquals(200, $this->venta->fresh()->psv_total);
        $this->assertContains($otroProducto->psk_id, array_column(Facturacion::first()->fac_original, 'psk_id'));
        $this->assertNotContains($otroProducto->psk_id, array_column(Facturacion::first()->fac_partidas, 'psk_id'));
        $this->get($this->ruta('show'))->assertOk()->assertSee('Factura simulada')->assertSee('pendiente de definición');
    }

    public function test_venta_de_un_almacen_factura_directo_con_descuentos_sin_habilitar_el_almacen(): void
    {
        $this->venta->detalle()->update(['pvd_alm_id' => $this->origen->alm_id]);
        $this->venta->update(['psv_total' => 175, 'psv_descuento' => 25]);
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertOk()->assertJsonPath('data.fac_total', '175.00')->assertJsonPath('data.fac_diferencia', '0.00')->assertJsonPath('data.fac_inventario_estado', 'sin_ajuste');
        $this->get($this->ruta('show'))->assertOk()->assertSee('Ticket a facturar');
    }

    public function test_valida_almacen_habilitado_sucursal_y_producto_asignado(): void
    {
        $this->putJson($this->ruta('guardar'), $this->ajuste(['almacen_id' => $this->origen->alm_id]))->assertUnprocessable()->assertJsonValidationErrors('almacen_id');
        $this->sku->producto->almacenesPermitidos()->sync([$this->origen->alm_id]);
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->getJson($this->ruta('productos').'?almacen_id='.$this->destino->alm_id)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($this->ruta('productos').'?almacen_id='.$this->origen->alm_id)->assertUnprocessable();
        $this->sku->producto->almacenesPermitidos()->sync([$this->destino->alm_id]);
        $otra = Sucursal::create(['scl_nombre' => 'Otra', 'scl_clave' => 'OTRA-FAC', 'scl_estatus' => 'activo']);
        $this->destino->update(['alm_scl_id' => $otra->scl_id]);
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertUnprocessable()->assertJsonValidationErrors('almacen_id');
        $this->assertDatabaseCount('tbl_facturacion_fac', 0);
    }

    public function test_rechaza_version_obsoleta_y_edicion_despues_de_emitir(): void
    {
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertOk();
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->putJson($this->ruta('guardar'), $this->ajuste(['version' => 1, 'items' => [['psk_id' => $this->sku->psk_id, 'cantidad' => 1, 'precio' => 50]]]))->assertOk()->assertJsonPath('data.fac_diferencia', '-150.00');
        $this->postJson($this->ruta('emitir'), ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->postJson($this->ruta('emitir'), ['version' => 2])->assertOk();
        $this->putJson($this->ruta('guardar'), $this->ajuste(['version' => 3]))->assertUnprocessable();
        $this->assertSame('50.00', Facturacion::first()->fac_total);
    }

    public function test_no_factura_canceladas_ni_productos_desactivados_tras_guardar(): void
    {
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertOk();
        $this->sku->update(['psk_estatus' => 'inactivo']);
        $this->postJson($this->ruta('emitir'), ['version' => 1])->assertUnprocessable();
        $this->venta->update(['psv_estatus' => 'cancelada']);
        $this->postJson($this->ruta('emitir'), ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('venta');
        $this->getJson(route('desktop.facturacion.data'))->assertOk()->assertJsonPath('data.0.bloqueada', true);
    }

    public function test_catalogo_y_vistas_respetan_diseno_y_permisos(): void
    {
        $this->get(route('desktop.facturacion.index'))->assertOk()->assertSee('Facturación')->assertSee('desktop-list');
        $this->get($this->ruta('show'))->assertOk()->assertSee('Ticket original')->assertSee('Ticket ajustado')->assertSee('Guardar ajuste');
        $this->getJson($this->ruta('productos').'?almacen_id='.$this->destino->alm_id)->assertOk()->assertJsonPath('data.0.psk_id', $this->sku->psk_id)->assertJsonPath('data.0.existencia', 10);
        $this->getJson(route('desktop.facturacion.data', ['buscar' => 'FAC-TEST', 'hasta' => now()->toDateString()]))->assertOk()->assertJsonPath('data.0.mixta', true);
        DB::table('tbl_usuario_roles_url')->where('url_usr_id', $this->admin->usr_id)->delete();
        $this->getJson(route('desktop.facturacion.data'))->assertForbidden();
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertForbidden();
    }

    public function test_no_admite_cantidades_invalidas_ni_partidas_duplicadas(): void
    {
        foreach ([0, -1, 1.234, 1.5] as $cantidad) {
            $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => [['psk_id' => $this->sku->psk_id, 'cantidad' => $cantidad, 'precio' => 100]]]))->assertUnprocessable();
        }
        $linea = $this->ajuste()['items'][0];
        $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => [$linea, $linea]]))->assertUnprocessable();
        $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => []]))->assertUnprocessable();
        $this->assertDatabaseCount('tbl_facturacion_fac', 0);
    }

    public function test_habilitacion_se_guarda_desde_almacenes_y_se_preserva_si_un_cliente_anterior_omite_el_campo(): void
    {
        $datos = [
            'alm_scl_id' => $this->origen->alm_scl_id, 'alm_tal_id' => $this->origen->alm_tal_id,
            'alm_nombre' => $this->origen->alm_nombre, 'alm_estatus' => 'activo', 'alm_permite_facturar' => true,
        ];
        $ruta = route('desktop.operacion.gestion_configuraciones.almacenes.update', $this->origen->alm_id);
        $this->putJson($ruta, $datos)->assertOk();
        $this->assertTrue((bool) $this->origen->fresh()->alm_permite_facturar);
        unset($datos['alm_permite_facturar']);
        $this->putJson($ruta, $datos)->assertOk();
        $this->assertTrue((bool) $this->origen->fresh()->alm_permite_facturar);
        $this->getJson(route('desktop.operacion.gestion_configuraciones.almacenes.show', $this->origen->alm_id))->assertOk()->assertJsonPath('data.alm_permite_facturar', true);
        $this->putJson($ruta, $datos + ['alm_permite_facturar' => false])->assertOk();
        $this->assertFalse((bool) $this->origen->fresh()->alm_permite_facturar);
    }

    public function test_permiso_de_consulta_no_permite_escribir_y_la_sucursal_limita_todos_los_endpoints(): void
    {
        $usuario = Usuario::create(['usr_usuario' => 'facturas-consulta', 'usr_nombre' => 'Consulta', 'usr_password' => bcrypt('test-password'), 'usr_estatus' => 'activo']);
        $rol = \App\Models\Rol::create(['rol_nombre' => 'Facturación consulta', 'rol_estatus' => 'activo']);
        \App\Models\UsuarioRol::create(['url_usr_id' => $usuario->usr_id, 'url_rol_id' => $rol->rol_id, 'url_estatus' => 'activo']);
        foreach (['ver', 'ajustar', 'emitir'] as $accion) {
            \App\Models\RolPermiso::create(['rpm_rol_id' => $rol->rol_id, 'rpm_prm_id' => \App\Models\Permiso::where('prm_clave', 'facturacion.'.$accion)->value('prm_id'), 'rpm_estatus' => 'activo']);
        }
        $this->actingAs($usuario);
        $this->getJson(route('desktop.facturacion.data'))->assertOk()->assertJsonCount(0, 'data');
        $this->get($this->ruta('show'))->assertNotFound();
        $this->getJson($this->ruta('productos').'?almacen_id='.$this->destino->alm_id)->assertNotFound();
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertNotFound();
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertNotFound();
        \App\Models\UsuarioSucursal::create(['usc_usr_id' => $usuario->usr_id, 'usc_scl_id' => $this->venta->psv_scl_id, 'usc_estatus' => 'activo']);
        $this->getJson(route('desktop.facturacion.data'))->assertOk()->assertJsonPath('data.0.id', $this->venta->psv_id);
        $ids = \App\Models\Permiso::whereIn('prm_clave', ['facturacion.ajustar', 'facturacion.emitir'])->pluck('prm_id');
        DB::table('tbl_rol_permisos_rpm')->where('rpm_rol_id', $rol->rol_id)->whereIn('rpm_prm_id', $ids)->delete();
        $this->get($this->ruta('show'))->assertOk()->assertDontSee('id="ajuste-guardar"', false);
        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertForbidden();
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertForbidden();
    }

    public function test_listado_clasifica_situaciones_con_conteos_y_motivos(): void
    {
        $simple = $this->venta->replicate(['psv_folio'])->fill(['psv_folio' => 'FAC-SIMPLE']);
        $simple->save();
        $simple->detalle()->create(['pvd_psk_id' => $this->sku->psk_id, 'pvd_alm_id' => $this->origen->alm_id, 'pvd_cantidad' => 1, 'pvd_precio_unitario' => 80, 'pvd_importe' => 80]);
        $cancelada = $this->venta->replicate(['psv_folio'])->fill(['psv_folio' => 'FAC-CANC', 'psv_estatus' => 'cancelada']);
        $cancelada->save();
        $cancelada->detalle()->create(['pvd_psk_id' => $this->sku->psk_id, 'pvd_alm_id' => $this->origen->alm_id, 'pvd_cantidad' => 1, 'pvd_precio_unitario' => 80, 'pvd_importe' => 80]);
        $folios = fn (string $estado) => collect($this->getJson(route('desktop.facturacion.data', ['estado' => $estado, 'buscar' => 'FAC-']))->assertOk()->json('data'))->pluck('folio')->all();

        $respuesta = $this->getJson(route('desktop.facturacion.data', ['buscar' => 'FAC-']))->assertOk()
            ->assertJsonPath('resumen.requiere_ajuste', 1)->assertJsonPath('resumen.lista', 1)
            ->assertJsonPath('resumen.revision', 1)->assertJsonPath('resumen.simulada', 0);
        $filas = collect($respuesta->json('data'))->keyBy('folio');
        $this->assertSame('requiere_ajuste', $filas['FAC-TEST']['situacion']);
        $this->assertSame('lista', $filas['FAC-SIMPLE']['situacion']);
        $this->assertSame('Venta cancelada.', $filas['FAC-CANC']['motivo']);
        $this->assertSame(['FAC-TEST'], $folios('requiere_ajuste'));
        $this->assertSame(['FAC-SIMPLE'], $folios('lista'));
        $this->assertSame(['FAC-CANC'], $folios('revision'));

        $this->putJson($this->ruta('guardar'), $this->ajuste())->assertOk();
        $this->assertEqualsCanonicalizing(['FAC-TEST', 'FAC-SIMPLE'], $folios('lista'));
        $this->postJson(route('desktop.facturacion.emitir', $simple->psv_id), ['version' => 0])->assertOk();
        $this->assertSame(['FAC-SIMPLE'], $folios('simulada'));
        $this->assertSame([], $folios('requiere_ajuste'));
    }

    public function test_aviso_sin_almacen_habilitado_y_preseleccion_del_unico_habilitado(): void
    {
        $this->get($this->ruta('show'))->assertOk()->assertSee('<option value="'.$this->destino->alm_id.'" selected>', false);
        $this->destino->update(['alm_permite_facturar' => false]);
        $this->get(route('desktop.facturacion.index'))->assertOk()->assertSee('Aún no hay un almacén habilitado para facturar');
        $this->get($this->ruta('show'))->assertOk()->assertSee('Falta habilitar un almacén para facturar')->assertSee('Administración → Almacenes');
        $this->getJson(route('desktop.facturacion.data'))->assertOk()->assertJsonPath('data.0.sin_almacen', true);
        $this->assertFalse((bool) $this->origen->fresh()->alm_permite_facturar);
    }

    public function test_busqueda_por_codigo_de_barras_y_por_ids_solo_del_almacen_habilitado(): void
    {
        $this->sku->update(['psk_codigo_barras' => '7501234567890']);
        $ajeno = ProductoSku::where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();
        $ajeno->producto->almacenesPermitidos()->sync([$this->origen->alm_id]);
        $url = $this->ruta('productos').'?almacen_id='.$this->destino->alm_id;
        $this->getJson($url.'&buscar=7501234567890')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.exacto', true)->assertJsonPath('data.0.codigo_barras', '7501234567890');
        $this->getJson($url.'&buscar='.urlencode("SKU'POLO"))->assertOk()->assertJsonPath('data.0.psk_id', $this->sku->psk_id);
        $this->getJson($url.'&ids[]='.$this->sku->psk_id.'&ids[]='.$ajeno->psk_id)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.psk_id', $this->sku->psk_id);
    }

    public function test_errores_de_partida_se_devuelven_por_indice_para_mostrarlos_junto_al_campo(): void
    {
        $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => [['psk_id' => $this->sku->psk_id, 'cantidad' => 1.5, 'precio' => 100]]]))
            ->assertUnprocessable()->assertJsonValidationErrors(['items', 'items.0.cantidad']);
        $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => [['psk_id' => $this->sku->psk_id, 'cantidad' => 0, 'precio' => -1]]]))
            ->assertUnprocessable()->assertJsonValidationErrors([
                'items.0.cantidad' => 'La cantidad debe ser mayor a cero.', 'items.0.precio' => 'El precio no puede ser negativo.',
            ]);
        $this->assertDatabaseCount('tbl_facturacion_fac', 0);
    }

    /** Texto de los flujos comprimidos del PDF, para revisar su contenido sin herramientas externas. */
    private function textoPdf(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $flujos);

        return collect($flujos[1])->map(fn ($flujo) => @gzuncompress($flujo) ?: $flujo)->implode("\n");
    }

    public function test_pdf_de_ticket_ajustado_muestra_solo_partidas_ajustadas_y_no_modifica_nada(): void
    {
        $sustituto = ProductoSku::where('psk_codigo', 'SKU-GAB-120-AZM')->firstOrFail();
        $sustituto->producto->almacenesPermitidos()->sync([$this->destino->alm_id]);
        $this->putJson($this->ruta('guardar'), $this->ajuste(['items' => [['psk_id' => $sustituto->psk_id, 'cantidad' => 2.75, 'precio' => 80]], 'notas' => 'Sustitución por gabardina']))->assertOk();
        $this->getJson($this->ruta('pdf'))->assertNotFound();
        $folio = $this->postJson($this->ruta('emitir'), ['version' => 1])->assertOk()->json('data.fac_folio');
        $antes = [DB::table('tbl_existencias_almacen_exa')->get()->toJson(), DB::table('tbl_pos_ventas_psv')->get()->toJson(),
            $this->venta->detalle()->get()->toJson(), DB::table('tbl_facturacion_fac')->get()->toJson(), DB::table('tbl_movimientos_inventario_min')->count()];

        $ver = $this->get($this->ruta('pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline; filename="factura-simulada-'.$folio.'.pdf"', $ver->headers->get('Content-Disposition'));
        $texto = $this->textoPdf($ver->getContent());
        $uuid = Facturacion::first()->fac_documento['cfdi']['timbre']['uuid'];
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/', $uuid);
        $this->get($this->ruta('show'))->assertOk()->assertSee($uuid)->assertSee('Descargar PDF');
        foreach (['FACTURA SIMULADA', $folio, 'FAC-TEST', 'destino', 'SKU-GAB-120-AZM', '2.75', '$68.97', '$220.00', 'Sustitución por gabardina', 'Sin valor fiscal', $uuid, 'XAXX010101000', 'S01 - Sin efectos fiscales', 'CFDI SIMULADO', 'DOSCIENTOS VEINTE PESOS 00/100 M.N.'] as $esperado) {
            $this->assertStringContainsString($esperado, mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1').$texto, "Falta {$esperado} en el PDF");
        }
        $this->assertStringNotContainsString('SKU-POLO-CH-AZM', $texto, 'No debe mezclar las partidas originales.');
        $this->assertStringNotContainsString('$200.00', $texto, 'No debe mostrar el total original.');
        $this->assertStringNotContainsString('sat.gob.mx', $texto, 'Nada debe apuntar a la verificación real del SAT.');

        $descarga = $this->get($this->ruta('pdf').'?descargar=1')->assertOk();
        $this->assertStringStartsWith('attachment; filename="factura-simulada-'.$folio.'.pdf"', $descarga->headers->get('Content-Disposition'));
        $this->assertStringContainsString($uuid, $this->textoPdf($descarga->getContent()), 'Cada descarga conserva el mismo UUID simulado.');
        $this->assertSame($antes, [DB::table('tbl_existencias_almacen_exa')->get()->toJson(), DB::table('tbl_pos_ventas_psv')->get()->toJson(),
            $this->venta->detalle()->get()->toJson(), DB::table('tbl_facturacion_fac')->get()->toJson(), DB::table('tbl_movimientos_inventario_min')->count()]);
        $this->assertDatabaseCount('tbl_facturacion_fac', 1);
    }

    public function test_pdf_directo_conserva_descuentos_registrados_y_datos_congelados_al_emitir(): void
    {
        $this->venta->detalle()->update(['pvd_alm_id' => $this->origen->alm_id]);
        $this->venta->detalle()->first()->update(['pvd_descuento_importe' => 10, 'pvd_importe' => 90]);
        $this->venta->update(['psv_subtotal' => 190, 'psv_descuento' => 15, 'psv_credito_cambio' => 25, 'psv_total' => 150, 'psv_metodo_pago' => 'efectivo']);
        $cliente = \App\Models\Cliente::create(['cli_nombre' => 'Ana', 'cli_apellido_paterno' => 'Ruiz', 'cli_rfc' => 'RUAA800101XX1', 'cli_estatus' => 'activo']);
        $this->venta->update(['psv_cli_id' => $cliente->cli_id]);
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertOk();
        $documento = Facturacion::first()->fac_documento;
        $this->assertSame('directa', $documento['origen']);
        $this->assertEquals(['descuento_partidas' => 10, 'subtotal' => 190, 'descuento_global' => 15, 'credito_cambio' => 25, 'total' => 150], $documento['importes']);

        $cliente->update(['cli_nombre' => 'Cambiado']);
        $texto = $this->textoPdf($this->get($this->ruta('pdf'))->assertOk()->getContent());
        foreach (['ANA RUIZ', 'RUAA800101XX1', 'G03 - Gastos en general', 'descuento general $15.00', 'crédito de cambio $25.00', 'IVA trasladado 16%', '$150.00', 'CIENTO CINCUENTA PESOS 00/100 M.N.', '01 - Efectivo'] as $esperado) {
            $this->assertStringContainsString($esperado, mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1').$texto, "Falta {$esperado} en el PDF");
        }
        $this->assertStringNotContainsString('CAMBIADO', $texto);
        $cfdi = $documento['cfdi'];
        $this->assertEquals(150, $cfdi['comprobante']['total'], 'El total del CFDI simulado coincide con el facturado.');
        $this->assertEqualsWithDelta($cfdi['comprobante']['subtotal'] - $cfdi['comprobante']['descuento'] + $cfdi['comprobante']['iva'], 150, 0.001);
        $this->assertEqualsWithDelta(($cfdi['comprobante']['subtotal'] - $cfdi['comprobante']['descuento']) * 0.16, $cfdi['comprobante']['iva'], 0.02);
        $this->assertSame(344, strlen($cfdi['timbre']['sello_cfd']));
        $this->assertSame('Sin configurar', $cfdi['emisor']['rfc'], 'El RFC del emisor no se inventa.');
    }

    public function test_total_con_letra_y_qr_simulado(): void
    {
        $cfdi = app(\App\Services\Operacion\CfdiSimuladoService::class);
        $this->assertSame('UN PESO 00/100 M.N.', $cfdi->totalConLetra(1));
        $this->assertSame('VEINTIÚN PESOS 50/100 M.N.', $cfdi->totalConLetra(21.5));
        $this->assertSame('CIEN PESOS 00/100 M.N.', $cfdi->totalConLetra(100));
        $this->assertSame('VEINTICINCO MIL TRES PESOS 22/100 M.N.', $cfdi->totalConLetra(25003.22));
        $this->assertSame('UN MILLÓN DOSCIENTOS UN MIL CIENTO DIECISÉIS PESOS 09/100 M.N.', $cfdi->totalConLetra(1201116.09));
        $qr = $cfdi->textoQr(['timbre' => ['uuid' => 'X'], 'emisor' => ['rfc' => 'A'], 'receptor' => ['rfc' => 'B'], 'comprobante' => ['total' => 1]], 'SIM-1');
        $this->assertStringContainsString('SIN VALOR FISCAL', $qr);
        $this->assertStringNotContainsString('sat.gob.mx', $qr);
    }

    public function test_pdf_respeta_permiso_y_sucursal(): void
    {
        $this->venta->detalle()->update(['pvd_alm_id' => $this->origen->alm_id]);
        $this->postJson($this->ruta('emitir'), ['version' => 0])->assertOk();
        $usuario = Usuario::create(['usr_usuario' => 'facturas-pdf', 'usr_nombre' => 'Consulta', 'usr_password' => bcrypt('test-password'), 'usr_estatus' => 'activo']);
        $rol = \App\Models\Rol::create(['rol_nombre' => 'Facturación PDF', 'rol_estatus' => 'activo']);
        \App\Models\UsuarioRol::create(['url_usr_id' => $usuario->usr_id, 'url_rol_id' => $rol->rol_id, 'url_estatus' => 'activo']);
        $this->actingAs($usuario)->get($this->ruta('pdf'))->assertForbidden();
        \App\Models\RolPermiso::create(['rpm_rol_id' => $rol->rol_id, 'rpm_prm_id' => \App\Models\Permiso::where('prm_clave', 'facturacion.ver')->value('prm_id'), 'rpm_estatus' => 'activo']);
        $this->actingAs($usuario)->get($this->ruta('pdf'))->assertNotFound();
        \App\Models\UsuarioSucursal::create(['usc_usr_id' => $usuario->usr_id, 'usc_scl_id' => $this->venta->psv_scl_id, 'usc_estatus' => 'activo']);
        $this->actingAs($usuario)->get($this->ruta('pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($usuario)->get($this->ruta('show'))->assertOk()->assertSee('Descargar PDF');
    }
}
