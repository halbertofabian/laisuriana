<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Desktop\OperacionCatalogoComercialController;
use App\Http\Controllers\Desktop\OperacionGestionConfiguracionesController;
use App\Http\Controllers\Desktop\OperacionInventarioController;
use App\Http\Controllers\Demo\DataTableDemoController;
use App\Http\Controllers\Operacion\CatalogoComercialController;
use App\Http\Controllers\Operacion\CajaController;
use App\Http\Controllers\Operacion\ChecklistEntregableController;
use App\Http\Controllers\Operacion\ClienteController;
use App\Http\Controllers\Operacion\EscaneoProductoController;
use App\Http\Controllers\Operacion\InventarioBaseController;
use App\Http\Controllers\Operacion\PedidoPisoController;
use App\Http\Controllers\Operacion\PuntoVentaController;
use App\Http\Controllers\Operacion\SucursalAlmacenController;
use App\Http\Controllers\Operacion\TicketPersonalizacionController;
use App\Http\Controllers\Seguridad\BitacoraController;
use App\Http\Controllers\Seguridad\PermisoController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\UsuarioController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::pattern('tipo', 'marcas|lineas|categorias|unidades|conceptos|motivos');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/login/buscar-usuarios', [AuthController::class, 'buscarUsuarios'])->name('login.buscar_usuarios');
});

Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::post('/login', [AuthController::class, 'loginMobile'])
        ->name('login')
        ->withoutMiddleware([VerifyCsrfToken::class]);
    Route::get('/login/buscar-usuarios', [AuthController::class, 'buscarUsuariosMobile'])
        ->name('login.buscar_usuarios')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});

Route::middleware('auth')->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/sucursales', [SucursalAlmacenController::class, 'sucursalesMobile'])
        ->name('sucursales');
    Route::get('/almacenes', [SucursalAlmacenController::class, 'almacenesMobile'])
        ->name('almacenes');
    Route::get('/pedidos-piso/data', [PedidoPisoController::class, 'data'])
        ->name('pedidos_piso.data');
    Route::get('/pedidos-piso/productos/buscar', [PedidoPisoController::class, 'buscarProductos'])
        ->name('pedidos_piso.productos.buscar');
    Route::post('/pedidos-piso', [PedidoPisoController::class, 'store'])
        ->name('pedidos_piso.store')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});

Route::get('/operacion/catalogo-comercial/productos/imagen-movil/{token}', [CatalogoComercialController::class, 'vistaCargaMovilProducto'])
    ->name('operacion.catalogo_comercial.productos.imagen_movil');
Route::post('/operacion/catalogo-comercial/productos/imagen-movil/{token}', [CatalogoComercialController::class, 'subirImagenMovilProducto'])
    ->name('operacion.catalogo_comercial.productos.imagen_movil.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/desktop/dashboard', [DashboardController::class, 'desktop'])->name('desktop.dashboard');
    Route::get('/desktop/usuarios', [DashboardController::class, 'desktopUsuarios'])->name('desktop.usuarios');
    Route::get('/desktop/roles', [DashboardController::class, 'desktopRoles'])->name('desktop.roles');
    Route::get('/desktop/permisos', [DashboardController::class, 'desktopPermisos'])->name('desktop.permisos');
    Route::get('/desktop/bitacora', [DashboardController::class, 'desktopBitacora'])->name('desktop.bitacora');
    Route::prefix('desktop/operacion/catalogo-comercial')->name('desktop.operacion.catalogo_comercial.')->group(function () {
        Route::get('/', [OperacionCatalogoComercialController::class, 'index'])->name('index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/catalogos-base', [OperacionCatalogoComercialController::class, 'base'])->name('base.index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/atributos', [OperacionCatalogoComercialController::class, 'atributos'])->name('atributos.index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/atributos/data', [OperacionCatalogoComercialController::class, 'dataAtributos'])->name('atributos.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/atributos/{atributo}', [OperacionCatalogoComercialController::class, 'showAtributo'])->name('atributos.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/atributos', [OperacionCatalogoComercialController::class, 'storeAtributo'])->name('atributos.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/atributos/{atributo}', [OperacionCatalogoComercialController::class, 'updateAtributo'])->name('atributos.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/atributos/{atributo}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusAtributo'])->name('atributos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/atributos/{atributo}', [OperacionCatalogoComercialController::class, 'eliminarAtributo'])->name('atributos.destroy')->middleware('permiso:catalogo_comercial.eliminar');
        Route::get('/productos', [OperacionCatalogoComercialController::class, 'productos'])->name('productos.index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/productos/data', [OperacionCatalogoComercialController::class, 'dataProductos'])->name('productos.data')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/productos/imagen-temporal/iniciar', [OperacionCatalogoComercialController::class, 'iniciarCargaTemporalProducto'])->name('productos.imagen_temporal.iniciar')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/productos/imagen-temporal/{token}/estado', [OperacionCatalogoComercialController::class, 'estadoCargaTemporalProducto'])->name('productos.imagen_temporal.estado')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/productos/imagen-movil/{token}', [OperacionCatalogoComercialController::class, 'vistaCargaMovilProducto'])->name('productos.imagen_movil')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/productos/imagen-movil/{token}', [OperacionCatalogoComercialController::class, 'subirImagenMovilProducto'])->name('productos.imagen_movil.store')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/productos/nuevo', [OperacionCatalogoComercialController::class, 'crearProducto'])->name('productos.create')->middleware('permiso:catalogo_comercial.crear');
        Route::get('/productos/{producto}/editar', [OperacionCatalogoComercialController::class, 'editarProducto'])->name('productos.edit')->middleware('permiso:catalogo_comercial.editar');
        Route::get('/productos/{producto}', [OperacionCatalogoComercialController::class, 'showProducto'])->name('productos.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/productos', [OperacionCatalogoComercialController::class, 'storeProducto'])->name('productos.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/productos/{producto}', [OperacionCatalogoComercialController::class, 'updateProducto'])->name('productos.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/productos/{producto}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusProducto'])->name('productos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/productos/{producto}', [OperacionCatalogoComercialController::class, 'eliminarProducto'])->name('productos.destroy')->middleware('permiso:catalogo_comercial.eliminar');
        Route::get('/skus', [OperacionCatalogoComercialController::class, 'skus'])->name('skus.index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/data', [OperacionCatalogoComercialController::class, 'dataSkus'])->name('skus.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/agrupados', [OperacionCatalogoComercialController::class, 'dataSkusAgrupados'])->name('skus.agrupados')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/filtros', [OperacionCatalogoComercialController::class, 'filtrosSkus'])->name('skus.filtros')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/matriz', [OperacionCatalogoComercialController::class, 'matrizSkus'])->name('skus.matriz')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/{sku}/etiqueta-pdf', [OperacionCatalogoComercialController::class, 'generarEtiquetaSku'])->name('skus.etiqueta')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/skus/{sku}', [OperacionCatalogoComercialController::class, 'showSku'])->name('skus.show')->middleware('permiso:catalogo_comercial.ver');
        Route::put('/skus/{sku}', [OperacionCatalogoComercialController::class, 'updateSku'])->name('skus.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/skus/{sku}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusSku'])->name('skus.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/skus/{sku}', [OperacionCatalogoComercialController::class, 'eliminarSku'])->name('skus.destroy')->middleware('permiso:catalogo_comercial.eliminar');
        Route::get('/proveedores', [OperacionCatalogoComercialController::class, 'proveedores'])->name('proveedores.index')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/proveedores/data', [OperacionCatalogoComercialController::class, 'dataProveedores'])->name('proveedores.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/proveedores/{proveedor}', [OperacionCatalogoComercialController::class, 'showProveedor'])->name('proveedores.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/proveedores', [OperacionCatalogoComercialController::class, 'storeProveedor'])->name('proveedores.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/proveedores/{proveedor}', [OperacionCatalogoComercialController::class, 'updateProveedor'])->name('proveedores.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/proveedores/{proveedor}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusProveedor'])->name('proveedores.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/proveedores/{proveedor}', [OperacionCatalogoComercialController::class, 'eliminarProveedor'])->name('proveedores.destroy')->middleware('permiso:catalogo_comercial.eliminar');
        Route::get('/etiquetado', [OperacionCatalogoComercialController::class, 'etiquetado'])->name('etiquetado.index')->middleware('permiso:catalogo_comercial.ver');

        Route::get('/catalogos/{tipo}/data', [OperacionCatalogoComercialController::class, 'dataCatalogoBase'])->name('catalogos.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/catalogos/{tipo}/{id}', [OperacionCatalogoComercialController::class, 'showCatalogoBase'])->name('catalogos.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/catalogos/{tipo}', [OperacionCatalogoComercialController::class, 'storeCatalogoBase'])->name('catalogos.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/catalogos/{tipo}/{id}', [OperacionCatalogoComercialController::class, 'updateCatalogoBase'])->name('catalogos.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/catalogos/{tipo}/{id}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusCatalogoBase'])->name('catalogos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/catalogos/{tipo}/{id}', [OperacionCatalogoComercialController::class, 'eliminarCatalogoBase'])->name('catalogos.destroy')->middleware('permiso:catalogo_comercial.eliminar');

        Route::get('/modelos/data', [OperacionCatalogoComercialController::class, 'dataModelos'])->name('modelos.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/modelos/{modelo}', [OperacionCatalogoComercialController::class, 'showModelo'])->name('modelos.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/modelos', [OperacionCatalogoComercialController::class, 'storeModelo'])->name('modelos.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/modelos/{modelo}', [OperacionCatalogoComercialController::class, 'updateModelo'])->name('modelos.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/modelos/{modelo}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusModelo'])->name('modelos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/modelos/{modelo}', [OperacionCatalogoComercialController::class, 'eliminarModelo'])->name('modelos.destroy')->middleware('permiso:catalogo_comercial.eliminar');

        Route::get('/valores-atributo/data', [OperacionCatalogoComercialController::class, 'dataValoresAtributo'])->name('valores.data')->middleware('permiso:catalogo_comercial.ver');
        Route::get('/valores-atributo/{valor}', [OperacionCatalogoComercialController::class, 'showValorAtributo'])->name('valores.show')->middleware('permiso:catalogo_comercial.ver');
        Route::post('/valores-atributo', [OperacionCatalogoComercialController::class, 'storeValorAtributo'])->name('valores.store')->middleware('permiso:catalogo_comercial.crear');
        Route::put('/valores-atributo/{valor}', [OperacionCatalogoComercialController::class, 'updateValorAtributo'])->name('valores.update')->middleware('permiso:catalogo_comercial.editar');
        Route::patch('/valores-atributo/{valor}/estatus', [OperacionCatalogoComercialController::class, 'cambiarEstatusValorAtributo'])->name('valores.estatus')->middleware('permiso:catalogo_comercial.inactivar');
        Route::delete('/valores-atributo/{valor}', [OperacionCatalogoComercialController::class, 'eliminarValorAtributo'])->name('valores.destroy')->middleware('permiso:catalogo_comercial.eliminar');
    });
    Route::get('/desktop/operacion/pedido-piso', [\App\Http\Controllers\Desktop\OperacionPedidoPisoController::class, 'index'])
        ->name('desktop.operacion.pedido_piso.index')->middleware('permiso:pedido_piso.ver');
    Route::prefix('desktop/operacion/inventario')->name('desktop.operacion.inventario.')->group(function () {
        Route::get('/', [OperacionInventarioController::class, 'index'])->name('index')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias', [OperacionInventarioController::class, 'existencias'])->name('existencias.index')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias/data', [OperacionInventarioController::class, 'dataExistenciasMatriz'])->name('existencias.data')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias/exportar/excel', [OperacionInventarioController::class, 'exportarExcelExistenciasMatriz'])->name('existencias.exportar.excel')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias/exportar/pdf', [OperacionInventarioController::class, 'exportarPdfExistenciasMatriz'])->name('existencias.exportar.pdf')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias-negativas', [OperacionInventarioController::class, 'existenciasNegativas'])->name('existencias_negativas.index')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias-negativas/data', [OperacionInventarioController::class, 'dataExistenciasNegativas'])->name('existencias_negativas.data')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias-negativas/exportar/excel', [OperacionInventarioController::class, 'exportarExcelExistenciasNegativas'])->name('existencias_negativas.exportar.excel')->middleware('permiso:inventario_base.ver');
        Route::get('/existencias-negativas/exportar/pdf', [OperacionInventarioController::class, 'exportarPdfExistenciasNegativas'])->name('existencias_negativas.exportar.pdf')->middleware('permiso:inventario_base.ver');
        Route::get('/productos/buscar', [OperacionInventarioController::class, 'buscarProductosBase'])->name('productos.buscar')->middleware('permiso:inventario_base.ver');
        Route::get('/kardex/data', [OperacionInventarioController::class, 'dataKardex'])->name('kardex.data')->middleware('permiso:inventario_base.ver');
        Route::get('/kardex/{sku}/detalle', [OperacionInventarioController::class, 'kardexDetalle'])->name('kardex.detalle')->middleware('permiso:inventario_base.ver');
        Route::get('/negativos-por-sesion', [OperacionInventarioController::class, 'negativosSesion'])->name('negativos_sesion.index')->middleware('permiso:inventario_base.ver');
        Route::get('/negativos-por-sesion/data', [OperacionInventarioController::class, 'dataNegativosSesion'])->name('negativos_sesion.data')->middleware('permiso:inventario_base.ver');
        Route::get('/negativos-por-sesion/exportar/excel', [OperacionInventarioController::class, 'exportarExcelNegativosSesion'])->name('negativos_sesion.exportar.excel')->middleware('permiso:inventario_base.ver');
        Route::get('/negativos-por-sesion/exportar/pdf', [OperacionInventarioController::class, 'exportarPdfNegativosSesion'])->name('negativos_sesion.exportar.pdf')->middleware('permiso:inventario_base.ver');
        Route::get('/recibir', [OperacionInventarioController::class, 'recibir'])->name('recibir.index')->middleware('permiso:inventario_base.ver');
        Route::get('/recibir/productos/buscar', [OperacionInventarioController::class, 'buscarProductosRecibir'])->name('recibir.productos.buscar')->middleware('permiso:inventario_base.ver');
        Route::get('/recibir/productos/{producto}/matriz', [OperacionInventarioController::class, 'matrizRecibir'])->name('recibir.productos.matriz')->middleware('permiso:inventario_base.ver');
        Route::post('/recibir/borrador', [OperacionInventarioController::class, 'storeRecibirBorrador'])->name('recibir.borrador')->middleware('permiso:inventario_base.entrada');
        Route::post('/recibir/confirmar', [OperacionInventarioController::class, 'confirmarRecibir'])->name('recibir.confirmar')->middleware('permiso:inventario_base.entrada');
        Route::get('/recepciones', [OperacionInventarioController::class, 'recepciones'])->name('recepciones.index')->middleware('permiso:inventario_base.ver');
        Route::get('/recepciones/data', [OperacionInventarioController::class, 'dataRecepciones'])->name('recepciones.data')->middleware('permiso:inventario_base.ver');
        Route::get('/recepciones/{recepcion}', [OperacionInventarioController::class, 'showRecepcion'])->name('recepciones.show')->middleware('permiso:inventario_base.ver');
        Route::get('/recepciones/{recepcion}/reporte-pdf', [OperacionInventarioController::class, 'reportePdfRecepcion'])->name('recepciones.reporte_pdf')->middleware('permiso:inventario_base.ver');
        Route::post('/recepciones/{recepcion}/cancelar', [OperacionInventarioController::class, 'cancelarRecepcion'])->name('recepciones.cancelar')->middleware('permiso:inventario_base.cancelar');
        Route::get('/salidas', [OperacionInventarioController::class, 'salidas'])->name('salidas.index')->middleware('permiso:inventario_base.ver');
        Route::get('/salidas/data', [OperacionInventarioController::class, 'dataSalidas'])->name('salidas.data')->middleware('permiso:inventario_base.ver');
        Route::get('/salidas/registrar', [OperacionInventarioController::class, 'salidasRegistrar'])->name('salidas.registrar')->middleware('permiso:inventario_base.salida');
        Route::get('/kardex', [OperacionInventarioController::class, 'kardex'])->name('kardex.index')->middleware('permiso:inventario_base.ver');
        Route::get('/minimos', [OperacionInventarioController::class, 'minimos'])->name('minimos.index')->middleware('permiso:inventario_base.ver');
        Route::get('/minimos/bajo/data', [OperacionInventarioController::class, 'dataBajoMinimo'])->name('minimos.bajo.data')->middleware('permiso:inventario_base.ver');
    });
    Route::prefix('desktop/operacion/gestion-configuraciones')->name('desktop.operacion.gestion_configuraciones.')->group(function () {
        Route::get('/', [OperacionGestionConfiguracionesController::class, 'index'])->name('index');

        Route::get('/sucursales', [OperacionGestionConfiguracionesController::class, 'sucursales'])
            ->name('sucursales.index')
            ->middleware('permiso:sucursal.ver');
        Route::get('/sucursales/data', [OperacionGestionConfiguracionesController::class, 'dataSucursales'])
            ->name('sucursales.data')
            ->middleware('permiso:sucursal.ver');
        Route::get('/sucursales/{sucursal}', [OperacionGestionConfiguracionesController::class, 'showSucursal'])
            ->name('sucursales.show')
            ->middleware('permiso:sucursal.ver');
        Route::post('/sucursales', [OperacionGestionConfiguracionesController::class, 'storeSucursal'])
            ->name('sucursales.store')
            ->middleware('permiso:sucursal.crear');
        Route::put('/sucursales/{sucursal}', [OperacionGestionConfiguracionesController::class, 'updateSucursal'])
            ->name('sucursales.update')
            ->middleware('permiso:sucursal.editar');
        Route::patch('/sucursales/{sucursal}/estatus', [OperacionGestionConfiguracionesController::class, 'cambiarEstatusSucursal'])
            ->name('sucursales.estatus')
            ->middleware('permiso:sucursal.inactivar');

        Route::get('/almacenes', [OperacionGestionConfiguracionesController::class, 'almacenes'])
            ->name('almacenes.index')
            ->middleware('permiso:almacen.ver');
        Route::get('/almacenes/data', [OperacionGestionConfiguracionesController::class, 'dataAlmacenes'])
            ->name('almacenes.data')
            ->middleware('permiso:almacen.ver');
        Route::get('/almacenes/{almacen}', [OperacionGestionConfiguracionesController::class, 'showAlmacen'])
            ->name('almacenes.show')
            ->middleware('permiso:almacen.ver');
        Route::post('/almacenes', [OperacionGestionConfiguracionesController::class, 'storeAlmacen'])
            ->name('almacenes.store')
            ->middleware('permiso:almacen.crear');
        Route::put('/almacenes/{almacen}', [OperacionGestionConfiguracionesController::class, 'updateAlmacen'])
            ->name('almacenes.update')
            ->middleware('permiso:almacen.editar');
        Route::patch('/almacenes/{almacen}/estatus', [OperacionGestionConfiguracionesController::class, 'cambiarEstatusAlmacen'])
            ->name('almacenes.estatus')
            ->middleware('permiso:almacen.inactivar');
        Route::get('/tipos-almacen', [OperacionGestionConfiguracionesController::class, 'tiposAlmacen'])
            ->name('tipos_almacen.index')
            ->middleware('permiso:tipo_almacen.ver');
        Route::get('/tipos-almacen/data', [OperacionGestionConfiguracionesController::class, 'dataTiposAlmacen'])
            ->name('tipos_almacen.data')
            ->middleware('permiso:tipo_almacen.ver');
        Route::get('/tipos-almacen/{tipo_almacen}', [OperacionGestionConfiguracionesController::class, 'showTipoAlmacen'])
            ->name('tipos_almacen.show')
            ->middleware('permiso:tipo_almacen.ver');
        Route::post('/tipos-almacen', [OperacionGestionConfiguracionesController::class, 'storeTipoAlmacen'])
            ->name('tipos_almacen.store')
            ->middleware('permiso:tipo_almacen.crear');
        Route::put('/tipos-almacen/{tipo_almacen}', [OperacionGestionConfiguracionesController::class, 'updateTipoAlmacen'])
            ->name('tipos_almacen.update')
            ->middleware('permiso:tipo_almacen.editar');
        Route::patch('/tipos-almacen/{tipo_almacen}/estatus', [OperacionGestionConfiguracionesController::class, 'cambiarEstatusTipoAlmacen'])
            ->name('tipos_almacen.estatus')
            ->middleware('permiso:tipo_almacen.inactivar');
        Route::get('/cajas', [OperacionGestionConfiguracionesController::class, 'cajas'])
            ->name('cajas.index')
            ->middleware('permiso:caja.ver');
        Route::get('/cajas/data', [OperacionGestionConfiguracionesController::class, 'dataCajas'])
            ->name('cajas.data')
            ->middleware('permiso:caja.ver');
        Route::get('/cajas/{caja}', [OperacionGestionConfiguracionesController::class, 'showCaja'])
            ->name('cajas.show')
            ->middleware('permiso:caja.ver');
        Route::post('/cajas', [OperacionGestionConfiguracionesController::class, 'storeCaja'])
            ->name('cajas.store')
            ->middleware('permiso:caja.crear');
        Route::put('/cajas/{caja}', [OperacionGestionConfiguracionesController::class, 'updateCaja'])
            ->name('cajas.update')
            ->middleware('permiso:caja.editar');
        Route::patch('/cajas/{caja}/estatus', [OperacionGestionConfiguracionesController::class, 'cambiarEstatusCaja'])
            ->name('cajas.estatus')
            ->middleware('permiso:caja.inactivar');
        Route::get('/clientes', [OperacionGestionConfiguracionesController::class, 'clientes'])
            ->name('clientes.index')
            ->middleware('permiso:cliente.ver');
        Route::get('/clientes/data', [OperacionGestionConfiguracionesController::class, 'dataClientes'])
            ->name('clientes.data')
            ->middleware('permiso:cliente.ver');
        Route::get('/clientes/buscar-codigo-postal', [OperacionGestionConfiguracionesController::class, 'buscarCodigoPostal'])
            ->name('clientes.cp.buscar')
            ->middleware('permiso:cliente.ver');
        Route::get('/clientes/{cliente}', [OperacionGestionConfiguracionesController::class, 'showCliente'])
            ->name('clientes.show')
            ->middleware('permiso:cliente.ver');
        Route::post('/clientes', [OperacionGestionConfiguracionesController::class, 'storeCliente'])
            ->name('clientes.store')
            ->middleware('permiso:cliente.crear');
        Route::put('/clientes/{cliente}', [OperacionGestionConfiguracionesController::class, 'updateCliente'])
            ->name('clientes.update')
            ->middleware('permiso:cliente.editar');
        Route::patch('/clientes/{cliente}/estatus', [OperacionGestionConfiguracionesController::class, 'cambiarEstatusCliente'])
            ->name('clientes.estatus')
            ->middleware('permiso:cliente.inactivar');
        Route::get('/personalizar-ticket', [OperacionGestionConfiguracionesController::class, 'personalizarTicket'])
            ->name('ticket.index')
            ->middleware('permiso:caja.ver');
        Route::put('/personalizar-ticket', [OperacionGestionConfiguracionesController::class, 'updatePersonalizarTicket'])
            ->name('ticket.update')
            ->middleware('permiso:caja.editar');
    });
    Route::view('/mobile/descarga-apk', 'mobile.descarga-apk')->name('mobile.descarga-apk');

    Route::get('/pos', [PuntoVentaController::class, 'index'])->name('pos.index');
    Route::get('/pos/caja/estado', [PuntoVentaController::class, 'estadoCaja'])->name('pos.caja.estado');
    Route::get('/pos/clientes/buscar', [PuntoVentaController::class, 'buscarClientes'])->name('pos.clientes.buscar');
    Route::post('/pos/caja/abrir', [PuntoVentaController::class, 'abrirCaja'])->name('pos.caja.abrir');
    Route::post('/pos/caja/tomar', [PuntoVentaController::class, 'tomarCaja'])->name('pos.caja.tomar');
    Route::post('/pos/caja/abandonar', [PuntoVentaController::class, 'abandonarCaja'])->name('pos.caja.abandonar');
    Route::post('/pos/ventas/cobrar', [PuntoVentaController::class, 'cobrar'])->name('pos.ventas.cobrar');
    Route::get('/pos/ventas/dia', [PuntoVentaController::class, 'ventasDelDia'])->name('pos.ventas.dia');
    Route::get('/pos/pedidos-pendientes', [PuntoVentaController::class, 'pedidosPendientesCobro'])->name('pos.pedidos.pendientes');
    Route::get('/pos/ventas/{venta}/ticket', [PuntoVentaController::class, 'ticket'])->name('pos.ventas.ticket');

    Route::prefix('demo')->name('demo.')->group(function () {
        Route::get('/datatables', [DataTableDemoController::class, 'index'])->name('datatables');
        Route::get('/datatables/data', [DataTableDemoController::class, 'data'])->name('datatables.data');
    });

    Route::prefix('operacion')->name('operacion.')->group(function () {

        Route::prefix('catalogo-comercial')->name('catalogo_comercial.')->group(function () {
            Route::get('/', [CatalogoComercialController::class, 'index'])->name('index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/base', [CatalogoComercialController::class, 'base'])->name('base.index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/atributos', [CatalogoComercialController::class, 'atributos'])->name('atributos.index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/productos', [CatalogoComercialController::class, 'productos'])->name('productos.index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/skus', [CatalogoComercialController::class, 'skus'])->name('skus.index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/proveedores', [CatalogoComercialController::class, 'proveedores'])->name('proveedores.index')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/etiquetado', [CatalogoComercialController::class, 'etiquetado'])->name('etiquetado.index')->middleware('permiso:catalogo_comercial.ver');

            Route::get('/catalogos/{tipo}/data', [CatalogoComercialController::class, 'dataCatalogoBase'])->name('catalogos.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/catalogos/{tipo}/{id}', [CatalogoComercialController::class, 'showCatalogoBase'])->name('catalogos.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/catalogos/{tipo}', [CatalogoComercialController::class, 'storeCatalogoBase'])->name('catalogos.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/catalogos/{tipo}/{id}', [CatalogoComercialController::class, 'updateCatalogoBase'])->name('catalogos.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/catalogos/{tipo}/{id}/estatus', [CatalogoComercialController::class, 'cambiarEstatusCatalogoBase'])->name('catalogos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/catalogos/{tipo}/{id}', [CatalogoComercialController::class, 'eliminarCatalogoBase'])->name('catalogos.destroy')->middleware('permiso:catalogo_comercial.eliminar');

            Route::get('/atributos/data', [CatalogoComercialController::class, 'dataAtributos'])->name('atributos.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/atributos/{atributo}', [CatalogoComercialController::class, 'showAtributo'])->name('atributos.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/atributos', [CatalogoComercialController::class, 'storeAtributo'])->name('atributos.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/atributos/{atributo}', [CatalogoComercialController::class, 'updateAtributo'])->name('atributos.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/atributos/{atributo}/estatus', [CatalogoComercialController::class, 'cambiarEstatusAtributo'])->name('atributos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/atributos/{atributo}', [CatalogoComercialController::class, 'eliminarAtributo'])->name('atributos.destroy')->middleware('permiso:catalogo_comercial.eliminar');

            Route::get('/valores-atributo/data', [CatalogoComercialController::class, 'dataValoresAtributo'])->name('valores.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/valores-atributo/{valor}', [CatalogoComercialController::class, 'showValorAtributo'])->name('valores.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/valores-atributo', [CatalogoComercialController::class, 'storeValorAtributo'])->name('valores.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/valores-atributo/{valor}', [CatalogoComercialController::class, 'updateValorAtributo'])->name('valores.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/valores-atributo/{valor}/estatus', [CatalogoComercialController::class, 'cambiarEstatusValorAtributo'])->name('valores.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/valores-atributo/{valor}', [CatalogoComercialController::class, 'eliminarValorAtributo'])->name('valores.destroy')->middleware('permiso:catalogo_comercial.eliminar');

            Route::get('/productos/data', [CatalogoComercialController::class, 'dataProductos'])->name('productos.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/productos/{producto}', [CatalogoComercialController::class, 'showProducto'])->name('productos.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/productos', [CatalogoComercialController::class, 'storeProducto'])->name('productos.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/productos/{producto}', [CatalogoComercialController::class, 'updateProducto'])->name('productos.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/productos/{producto}/estatus', [CatalogoComercialController::class, 'cambiarEstatusProducto'])->name('productos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/productos/{producto}', [CatalogoComercialController::class, 'eliminarProducto'])->name('productos.destroy')->middleware('permiso:catalogo_comercial.eliminar');
            Route::get('/productos/{producto}/atributos-permitidos', [CatalogoComercialController::class, 'atributosPermitidosProducto'])->name('productos.atributos_permitidos')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/productos/imagen-temporal/iniciar', [CatalogoComercialController::class, 'iniciarCargaTemporalProducto'])->name('productos.imagen_temporal.iniciar')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/productos/imagen-temporal/{token}/estado', [CatalogoComercialController::class, 'estadoCargaTemporalProducto'])->name('productos.imagen_temporal.estado')->middleware('permiso:catalogo_comercial.ver');

            Route::get('/modelos/data', [CatalogoComercialController::class, 'dataModelos'])->name('modelos.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/modelos/{modelo}', [CatalogoComercialController::class, 'showModelo'])->name('modelos.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/modelos', [CatalogoComercialController::class, 'storeModelo'])->name('modelos.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/modelos/{modelo}', [CatalogoComercialController::class, 'updateModelo'])->name('modelos.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/modelos/{modelo}/estatus', [CatalogoComercialController::class, 'cambiarEstatusModelo'])->name('modelos.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/modelos/{modelo}', [CatalogoComercialController::class, 'eliminarModelo'])->name('modelos.destroy')->middleware('permiso:catalogo_comercial.eliminar');
            Route::get('/modelos-por-marca/{marca}', [CatalogoComercialController::class, 'modelosPorMarca'])->name('modelos.por_marca')->middleware('permiso:catalogo_comercial.ver');

            Route::get('/proveedores/data', [CatalogoComercialController::class, 'dataProveedores'])->name('proveedores.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/proveedores/{proveedor}', [CatalogoComercialController::class, 'showProveedor'])->name('proveedores.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/proveedores', [CatalogoComercialController::class, 'storeProveedor'])->name('proveedores.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/proveedores/{proveedor}', [CatalogoComercialController::class, 'updateProveedor'])->name('proveedores.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/proveedores/{proveedor}/estatus', [CatalogoComercialController::class, 'cambiarEstatusProveedor'])->name('proveedores.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/proveedores/{proveedor}', [CatalogoComercialController::class, 'eliminarProveedor'])->name('proveedores.destroy')->middleware('permiso:catalogo_comercial.eliminar');

            Route::get('/skus/data', [CatalogoComercialController::class, 'dataSkus'])->name('skus.data')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/skus/agrupados', [CatalogoComercialController::class, 'dataSkusAgrupados'])->name('skus.agrupados')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/skus/filtros', [CatalogoComercialController::class, 'filtrosSkus'])->name('skus.filtros')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/skus/matriz', [CatalogoComercialController::class, 'matrizSkus'])->name('skus.matriz')->middleware('permiso:catalogo_comercial.ver');
            Route::get('/skus/{sku}', [CatalogoComercialController::class, 'showSku'])->name('skus.show')->middleware('permiso:catalogo_comercial.ver');
            Route::post('/skus', [CatalogoComercialController::class, 'storeSku'])->name('skus.store')->middleware('permiso:catalogo_comercial.crear');
            Route::put('/skus/{sku}', [CatalogoComercialController::class, 'updateSku'])->name('skus.update')->middleware('permiso:catalogo_comercial.editar');
            Route::patch('/skus/{sku}/estatus', [CatalogoComercialController::class, 'cambiarEstatusSku'])->name('skus.estatus')->middleware('permiso:catalogo_comercial.inactivar');
            Route::delete('/skus/{sku}', [CatalogoComercialController::class, 'eliminarSku'])->name('skus.destroy')->middleware('permiso:catalogo_comercial.eliminar');
            Route::get('/skus/{sku}/etiqueta-pdf', [CatalogoComercialController::class, 'generarEtiquetaSku'])->name('skus.etiqueta')->middleware('permiso:catalogo_comercial.ver');
        });

        Route::prefix('sucursales-almacenes')->name('sucursales_almacenes.')->group(function () {
            Route::get('/', [SucursalAlmacenController::class, 'index'])->name('index')->middleware('permiso:sucursal.ver');
            Route::get('/sucursales', [SucursalAlmacenController::class, 'sucursales'])->name('sucursales.index')->middleware('permiso:sucursal.ver');
            Route::get('/almacenes', [SucursalAlmacenController::class, 'almacenes'])->name('almacenes.index')->middleware('permiso:almacen.ver');
            Route::get('/tipos-almacen', [SucursalAlmacenController::class, 'tipos'])->name('tipos.index')->middleware('permiso:tipo_almacen.ver');

            Route::get('/sucursales/data', [SucursalAlmacenController::class, 'dataSucursales'])->name('sucursales.data')->middleware('permiso:sucursal.ver');
            Route::get('/sucursales/{sucursal}', [SucursalAlmacenController::class, 'showSucursal'])->name('sucursales.show')->middleware('permiso:sucursal.ver');
            Route::post('/sucursales', [SucursalAlmacenController::class, 'storeSucursal'])->name('sucursales.store')->middleware('permiso:sucursal.crear');
            Route::put('/sucursales/{sucursal}', [SucursalAlmacenController::class, 'updateSucursal'])->name('sucursales.update')->middleware('permiso:sucursal.editar');
            Route::patch('/sucursales/{sucursal}/estatus', [SucursalAlmacenController::class, 'cambiarEstatusSucursal'])->name('sucursales.estatus')->middleware('permiso:sucursal.inactivar');
            Route::delete('/sucursales/{sucursal}', [SucursalAlmacenController::class, 'eliminarSucursal'])->name('sucursales.destroy')->middleware('permiso:sucursal.eliminar');

            Route::get('/almacenes/data', [SucursalAlmacenController::class, 'dataAlmacenes'])->name('almacenes.data')->middleware('permiso:almacen.ver');
            Route::get('/almacenes/{almacen}', [SucursalAlmacenController::class, 'showAlmacen'])->name('almacenes.show')->middleware('permiso:almacen.ver');
            Route::post('/almacenes', [SucursalAlmacenController::class, 'storeAlmacen'])->name('almacenes.store')->middleware('permiso:almacen.crear');
            Route::put('/almacenes/{almacen}', [SucursalAlmacenController::class, 'updateAlmacen'])->name('almacenes.update')->middleware('permiso:almacen.editar');
            Route::patch('/almacenes/{almacen}/estatus', [SucursalAlmacenController::class, 'cambiarEstatusAlmacen'])->name('almacenes.estatus')->middleware('permiso:almacen.inactivar');
            Route::delete('/almacenes/{almacen}', [SucursalAlmacenController::class, 'eliminarAlmacen'])->name('almacenes.destroy')->middleware('permiso:almacen.eliminar');

            Route::get('/tipos-almacen/data', [SucursalAlmacenController::class, 'dataTiposAlmacen'])->name('tipos.data')->middleware('permiso:tipo_almacen.ver');
            Route::get('/tipos-almacen/{tipo_almacen}', [SucursalAlmacenController::class, 'showTipoAlmacen'])->name('tipos.show')->middleware('permiso:tipo_almacen.ver');
            Route::post('/tipos-almacen', [SucursalAlmacenController::class, 'storeTipoAlmacen'])->name('tipos.store')->middleware('permiso:tipo_almacen.crear');
            Route::put('/tipos-almacen/{tipo_almacen}', [SucursalAlmacenController::class, 'updateTipoAlmacen'])->name('tipos.update')->middleware('permiso:tipo_almacen.editar');
            Route::patch('/tipos-almacen/{tipo_almacen}/estatus', [SucursalAlmacenController::class, 'cambiarEstatusTipoAlmacen'])->name('tipos.estatus')->middleware('permiso:tipo_almacen.inactivar');
            Route::delete('/tipos-almacen/{tipo_almacen}', [SucursalAlmacenController::class, 'eliminarTipoAlmacen'])->name('tipos.destroy')->middleware('permiso:tipo_almacen.eliminar');
        });

        Route::prefix('ticket-personalizacion')->name('ticket_personalizacion.')->group(function () {
            Route::get('/', [TicketPersonalizacionController::class, 'index'])->name('index')->middleware('permiso:caja.ver');
            Route::put('/', [TicketPersonalizacionController::class, 'update'])->name('update')->middleware('permiso:caja.editar');
        });

        Route::prefix('checklist-entregables')->name('checklist_entregables.')->group(function () {
            Route::get('/', [ChecklistEntregableController::class, 'index'])->name('index')->middleware('permiso:checklist_entregables.ver');
            Route::get('/data', [ChecklistEntregableController::class, 'data'])->name('data')->middleware('permiso:checklist_entregables.ver');
            Route::get('/{checklist}/detalle', [ChecklistEntregableController::class, 'detalle'])->name('detalle')->middleware('permiso:checklist_entregables.ver');

            Route::post('/', [ChecklistEntregableController::class, 'store'])->name('store')->middleware('permiso:checklist_entregables.crear');
            Route::put('/{checklist}', [ChecklistEntregableController::class, 'update'])->name('update')->middleware('permiso:checklist_entregables.editar');
            Route::post('/{checklist}/secciones', [ChecklistEntregableController::class, 'storeSeccion'])->name('secciones.store')->middleware('permiso:checklist_entregables.crear');
            Route::post('/secciones/{seccion}/items', [ChecklistEntregableController::class, 'storeItem'])->name('items.store')->middleware('permiso:checklist_entregables.crear');
            Route::patch('/items/{item}/revision', [ChecklistEntregableController::class, 'actualizarRevisionItem'])->name('items.revision')->middleware('permiso:checklist_entregables.editar');
        });

        Route::prefix('escaneo-productos')->name('escaneo_productos.')->group(function () {
            Route::get('/', [EscaneoProductoController::class, 'index'])->name('index')->middleware('permiso:escaneo_producto.ver');
            Route::get('/buscar', [EscaneoProductoController::class, 'buscar'])->name('buscar')->middleware('permiso:escaneo_producto.ver');
        });

        Route::prefix('inventario-base')->name('inventario_base.')->group(function () {
            Route::get('/', [InventarioBaseController::class, 'index'])->name('index')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias', [InventarioBaseController::class, 'existencias'])->name('existencias.index')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-matriz', [InventarioBaseController::class, 'existenciasMatriz'])->name('existencias_matriz.index')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-negativas', [InventarioBaseController::class, 'existenciasNegativas'])->name('existencias_negativas.index')->middleware('permiso:inventario_base.ver');
            Route::get('/negativos-por-sesion', [InventarioBaseController::class, 'negativosPorSesion'])->name('negativos_sesion.index')->middleware('permiso:inventario_base.ver');
            Route::get('/recibir', [InventarioBaseController::class, 'recibir'])->name('recibir.index')->middleware('permiso:inventario_base.ver');
            Route::get('/recepciones', [InventarioBaseController::class, 'recepciones'])->name('recepciones.index')->middleware('permiso:inventario_base.ver');
            Route::get('/salidas', [InventarioBaseController::class, 'salidas'])->name('salidas.index')->middleware('permiso:inventario_base.ver');
            Route::get('/kardex', [InventarioBaseController::class, 'kardex'])->name('kardex.index')->middleware('permiso:inventario_base.ver');
            Route::get('/kardex/{sku}/detalle', [InventarioBaseController::class, 'kardexDetalle'])->name('kardex.detalle')->middleware('permiso:inventario_base.ver');
            Route::get('/minimos', [InventarioBaseController::class, 'minimos'])->name('minimos.index')->middleware('permiso:inventario_base.ver');
            Route::get('/reportes', [InventarioBaseController::class, 'reportes'])->name('reportes.index')->middleware('permiso:inventario_base.ver');
            Route::get('/entradas-wizard', [InventarioBaseController::class, 'wizardEntradas'])->name('entradas_wizard')->middleware('permiso:inventario_base.ver');

            Route::get('/existencias/data', [InventarioBaseController::class, 'dataExistencias'])->name('existencias.data')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-matriz/data', [InventarioBaseController::class, 'dataExistenciasMatriz'])->name('existencias_matriz.data')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-matriz/exportar/excel', [InventarioBaseController::class, 'exportarExcelExistenciasMatriz'])->name('existencias_matriz.exportar.excel')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-matriz/exportar/pdf', [InventarioBaseController::class, 'exportarPdfExistenciasMatriz'])->name('existencias_matriz.exportar.pdf')->middleware('permiso:inventario_base.ver');
            Route::get('/existencias-negativas/data', [InventarioBaseController::class, 'dataExistenciasNegativas'])->name('existencias_negativas.data')->middleware('permiso:inventario_base.ver');
            Route::get('/productos-base/data', [InventarioBaseController::class, 'dataProductosBase'])->name('productos.data')->middleware('permiso:inventario_base.ver');
            Route::get('/productos-base/buscar', [InventarioBaseController::class, 'buscarProductosBase'])->name('productos.buscar')->middleware('permiso:inventario_base.ver');
            Route::get('/skus/buscar', [InventarioBaseController::class, 'buscarSkus'])->name('skus.buscar')->middleware('permiso:inventario_base.ver');
            Route::get('/kardex/data', [InventarioBaseController::class, 'dataKardex'])->name('kardex.data')->middleware('permiso:inventario_base.ver');
            Route::get('/negativos-por-sesion/data', [InventarioBaseController::class, 'dataNegativosPorSesion'])->name('negativos_sesion.data')->middleware('permiso:inventario_base.ver');
            Route::get('/minimos/bajo/data', [InventarioBaseController::class, 'dataBajoMinimo'])->name('minimos.bajo.data')->middleware('permiso:inventario_base.ver');
            Route::get('/reportes/entradas/data', [InventarioBaseController::class, 'dataReportesEntradas'])->name('reportes.entradas.data')->middleware('permiso:inventario_base.ver');
            Route::get('/reportes/entradas/{reporte}/pdf', [InventarioBaseController::class, 'verReporteEntradasPdf'])->name('reportes.entradas.ver')->middleware('permiso:inventario_base.ver');
            Route::get('/recepciones/data', [InventarioBaseController::class, 'dataRecepcionesMercancia'])->name('recepciones.data')->middleware('permiso:inventario_base.ver');
            Route::get('/recepciones/{recepcion}', [InventarioBaseController::class, 'showRecepcionMercancia'])->name('recepciones.show')->middleware('permiso:inventario_base.ver');
            Route::get('/recepciones/{recepcion}/reporte-pdf', [InventarioBaseController::class, 'verReporteRecepcionMercanciaPdf'])->name('recepciones.reporte_pdf')->middleware('permiso:inventario_base.ver');
            Route::get('/disponibilidad', [InventarioBaseController::class, 'disponibilidad'])->name('disponibilidad')->middleware('permiso:inventario_base.ver');
            Route::get('/salidas/resolver', [InventarioBaseController::class, 'resolverSalida'])->name('salidas.resolver')->middleware('permiso:inventario_base.ver');
            Route::get('/productos/{producto}/matriz', [InventarioBaseController::class, 'matrizProducto'])->name('productos.matriz')->middleware('permiso:inventario_base.ver');

            Route::post('/inventario-inicial', [InventarioBaseController::class, 'storeInventarioInicial'])->name('inicial.store')->middleware('permiso:inventario_base.inicial');
            Route::post('/inventario-inicial/masivo', [InventarioBaseController::class, 'storeInventarioInicialMasivo'])->name('inicial.masivo.store')->middleware('permiso:inventario_base.inicial');
            Route::post('/entradas/masivo', [InventarioBaseController::class, 'storeEntradaMasiva'])->name('entradas.masivo.store')->middleware('permiso:inventario_base.entrada');
            Route::post('/recepciones/borrador', [InventarioBaseController::class, 'storeRecepcionMercanciaDraft'])->name('recepciones.borrador.store')->middleware('permiso:inventario_base.entrada');
            Route::post('/recepciones/confirmar', [InventarioBaseController::class, 'confirmRecepcionMercancia'])->name('recepciones.confirmar')->middleware('permiso:inventario_base.entrada');
            Route::post('/recepciones/{recepcion}/cancelar', [InventarioBaseController::class, 'cancelarRecepcionMercancia'])->name('recepciones.cancelar')->middleware('permiso:inventario_base.cancelar');
            Route::post('/reportes/entradas-seleccionadas/pdf', [InventarioBaseController::class, 'reporteEntradasSeleccionadasPdf'])->name('reportes.entradas_seleccionadas.pdf')->middleware('permiso:inventario_base.ver');
            Route::post('/entradas', [InventarioBaseController::class, 'storeEntrada'])->name('entradas.store')->middleware('permiso:inventario_base.entrada');
            Route::post('/salidas', [InventarioBaseController::class, 'storeSalida'])->name('salidas.store')->middleware('permiso:inventario_base.salida');
            Route::post('/minimos', [InventarioBaseController::class, 'storeMinimo'])->name('minimos.store')->middleware('permiso:inventario_base.minimos');

            Route::post('/movimientos/{movimiento}/cancelar', [InventarioBaseController::class, 'cancelar'])->name('movimientos.cancelar')->middleware('permiso:inventario_base.cancelar');
            Route::post('/movimientos/{movimiento}/corregir', [InventarioBaseController::class, 'corregir'])->name('movimientos.corregir')->middleware('permiso:inventario_base.corregir');
        });

        Route::prefix('cajas')->name('cajas.')->group(function () {
            Route::get('/', [CajaController::class, 'index'])->name('index')->middleware('permiso:caja.ver');
            Route::get('/data', [CajaController::class, 'data'])->name('data')->middleware('permiso:caja.ver');
            Route::get('/{caja}', [CajaController::class, 'show'])->name('show')->middleware('permiso:caja.ver');
            Route::post('/', [CajaController::class, 'store'])->name('store')->middleware('permiso:caja.crear');
            Route::put('/{caja}', [CajaController::class, 'update'])->name('update')->middleware('permiso:caja.editar');
            Route::patch('/{caja}/estatus', [CajaController::class, 'cambiarEstatus'])->name('estatus')->middleware('permiso:caja.inactivar');
            Route::delete('/{caja}', [CajaController::class, 'eliminar'])->name('destroy')->middleware('permiso:caja.eliminar');
        });

        Route::prefix('clientes')->name('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index')->middleware('permiso:cliente.ver');
            Route::get('/data', [ClienteController::class, 'data'])->name('data')->middleware('permiso:cliente.ver');
            Route::get('/buscar-codigo-postal', [ClienteController::class, 'buscarCodigoPostal'])->name('cp.buscar')->middleware('permiso:cliente.ver');
            Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show')->middleware('permiso:cliente.ver');
            Route::post('/', [ClienteController::class, 'store'])->name('store')->middleware('permiso:cliente.crear');
            Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update')->middleware('permiso:cliente.editar');
            Route::patch('/{cliente}/estatus', [ClienteController::class, 'cambiarEstatus'])->name('estatus')->middleware('permiso:cliente.inactivar');
            Route::delete('/{cliente}', [ClienteController::class, 'eliminar'])->name('destroy')->middleware('permiso:cliente.eliminar');
        });

        Route::prefix('pedidos-piso')->name('pedidos_piso.')->group(function () {
            Route::get('/', [PedidoPisoController::class, 'index'])->name('index')->middleware('permiso:pedido_piso.ver');
            Route::get('/data', [PedidoPisoController::class, 'data'])->name('data')->middleware('permiso:pedido_piso.ver');
            Route::get('/productos/buscar', [PedidoPisoController::class, 'buscarProductos'])->name('productos.buscar')->middleware('permiso:pedido_piso.ver');
            Route::get('/productos/resolver', [PedidoPisoController::class, 'resolverProductoAlmacen'])->name('productos.resolver')->middleware('permiso:pedido_piso.ver');
            Route::get('/productos/validar', [PedidoPisoController::class, 'validarProductoAlmacen'])->name('productos.validar')->middleware('permiso:pedido_piso.ver');
            Route::get('/buscar-por-folio', [PedidoPisoController::class, 'buscarPorFolio'])->name('folio.buscar')->middleware('permiso:pedido_piso.ver');
            Route::get('/{pedido}/ticket', [PedidoPisoController::class, 'ticket'])->name('ticket')->middleware('permiso:pedido_piso.ver');
            Route::get('/{pedido}', [PedidoPisoController::class, 'show'])->name('show')->middleware('permiso:pedido_piso.ver');
            Route::post('/', [PedidoPisoController::class, 'store'])->name('store')->middleware('permiso:pedido_piso.crear');
            Route::put('/{pedido}', [PedidoPisoController::class, 'update'])->name('update')->middleware('permiso:pedido_piso.crear');
            Route::delete('/{pedido}', [PedidoPisoController::class, 'eliminar'])->name('destroy')->middleware('permiso:pedido_piso.crear');
        });

        Route::prefix('ventas')->name('ventas.')->group(function () {
            Route::get('/', [PuntoVentaController::class, 'ventasIndex'])->name('index');
            Route::get('/data', [PuntoVentaController::class, 'ventasData'])->name('data');
        });
    });

    Route::prefix('seguridad')->name('seguridad.')->group(function () {
        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('index')->middleware('permiso:usuario.ver');
            Route::get('/data', [UsuarioController::class, 'data'])->name('data')->middleware('permiso:usuario.ver');
            Route::get('/{usuario}', [UsuarioController::class, 'show'])->name('show')->middleware('permiso:usuario.ver');
            Route::post('/', [UsuarioController::class, 'store'])->name('store')->middleware('permiso:usuario.crear');
            Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update')->middleware('permiso:usuario.editar');
            Route::patch('/{usuario}/estatus', [UsuarioController::class, 'cambiarEstatus'])->name('estatus')->middleware('permiso:usuario.inactivar');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RolController::class, 'index'])->name('index')->middleware('permiso:rol.ver');
            Route::get('/data', [RolController::class, 'data'])->name('data')->middleware('permiso:rol.ver');
            Route::get('/{rol}', [RolController::class, 'show'])->name('show')->middleware('permiso:rol.ver');
            Route::post('/', [RolController::class, 'store'])->name('store')->middleware('permiso:rol.crear');
            Route::put('/{rol}', [RolController::class, 'update'])->name('update')->middleware('permiso:rol.editar');
            Route::patch('/{rol}/estatus', [RolController::class, 'cambiarEstatus'])->name('estatus')->middleware('permiso:rol.editar');
        });

        Route::prefix('permisos')->name('permisos.')->group(function () {
            Route::get('/', [PermisoController::class, 'index'])->name('index')->middleware('permiso:permiso.ver');
            Route::get('/data', [PermisoController::class, 'data'])->name('data')->middleware('permiso:permiso.ver');
        });

        Route::prefix('bitacora')->name('bitacora.')->group(function () {
            Route::get('/', [BitacoraController::class, 'index'])->name('index')->middleware('permiso:seguridad.ver_auditoria');
            Route::get('/accesos', [BitacoraController::class, 'accesos'])->name('accesos')->middleware('permiso:seguridad.ver_auditoria');
            Route::get('/acciones', [BitacoraController::class, 'acciones'])->name('acciones')->middleware('permiso:seguridad.ver_auditoria');
        });
    });
});
