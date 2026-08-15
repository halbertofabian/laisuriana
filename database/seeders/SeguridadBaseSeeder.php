<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\RolPermiso;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SeguridadBaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisosBase = [
            ['clave' => 'usuario.ver', 'descripcion' => 'Ver usuarios', 'modulo' => 'seguridad'],
            ['clave' => 'usuario.crear', 'descripcion' => 'Crear usuarios', 'modulo' => 'seguridad'],
            ['clave' => 'usuario.editar', 'descripcion' => 'Editar usuarios', 'modulo' => 'seguridad'],
            ['clave' => 'usuario.inactivar', 'descripcion' => 'Inactivar usuarios', 'modulo' => 'seguridad'],
            ['clave' => 'rol.ver', 'descripcion' => 'Ver roles', 'modulo' => 'seguridad'],
            ['clave' => 'rol.crear', 'descripcion' => 'Crear roles', 'modulo' => 'seguridad'],
            ['clave' => 'rol.editar', 'descripcion' => 'Editar roles', 'modulo' => 'seguridad'],
            ['clave' => 'rol.asignar_permisos', 'descripcion' => 'Asignar permisos a roles', 'modulo' => 'seguridad'],
            ['clave' => 'permiso.ver', 'descripcion' => 'Ver permisos', 'modulo' => 'seguridad'],
            ['clave' => 'seguridad.asignar_rol', 'descripcion' => 'Asignar rol a usuarios', 'modulo' => 'seguridad'],
            ['clave' => 'seguridad.ver_auditoria', 'descripcion' => 'Ver auditoria de seguridad', 'modulo' => 'seguridad'],
            ['clave' => 'sucursal.ver', 'descripcion' => 'Ver sucursales', 'modulo' => 'operacion'],
            ['clave' => 'sucursal.crear', 'descripcion' => 'Crear sucursales', 'modulo' => 'operacion'],
            ['clave' => 'sucursal.editar', 'descripcion' => 'Editar sucursales', 'modulo' => 'operacion'],
            ['clave' => 'sucursal.inactivar', 'descripcion' => 'Inactivar sucursales', 'modulo' => 'operacion'],
            ['clave' => 'sucursal.eliminar', 'descripcion' => 'Eliminar sucursales (lógico)', 'modulo' => 'operacion'],
            ['clave' => 'almacen.ver', 'descripcion' => 'Ver almacenes', 'modulo' => 'operacion'],
            ['clave' => 'almacen.crear', 'descripcion' => 'Crear almacenes', 'modulo' => 'operacion'],
            ['clave' => 'almacen.editar', 'descripcion' => 'Editar almacenes', 'modulo' => 'operacion'],
            ['clave' => 'almacen.inactivar', 'descripcion' => 'Inactivar almacenes', 'modulo' => 'operacion'],
            ['clave' => 'almacen.eliminar', 'descripcion' => 'Eliminar almacenes (lógico)', 'modulo' => 'operacion'],
            ['clave' => 'tipo_almacen.ver', 'descripcion' => 'Ver tipos de almacén', 'modulo' => 'operacion'],
            ['clave' => 'tipo_almacen.crear', 'descripcion' => 'Crear tipos de almacén', 'modulo' => 'operacion'],
            ['clave' => 'tipo_almacen.editar', 'descripcion' => 'Editar tipos de almacén', 'modulo' => 'operacion'],
            ['clave' => 'tipo_almacen.inactivar', 'descripcion' => 'Inactivar tipos de almacén', 'modulo' => 'operacion'],
            ['clave' => 'tipo_almacen.eliminar', 'descripcion' => 'Eliminar tipos de almacén (lógico)', 'modulo' => 'operacion'],
            ['clave' => 'catalogo_comercial.ver', 'descripcion' => 'Ver catálogo comercial', 'modulo' => 'comercial'],
            ['clave' => 'catalogo_comercial.crear', 'descripcion' => 'Crear registros del catálogo comercial', 'modulo' => 'comercial'],
            ['clave' => 'catalogo_comercial.editar', 'descripcion' => 'Editar registros del catálogo comercial', 'modulo' => 'comercial'],
            ['clave' => 'catalogo_comercial.inactivar', 'descripcion' => 'Activar/Inactivar registros del catálogo comercial', 'modulo' => 'comercial'],
            ['clave' => 'catalogo_comercial.eliminar', 'descripcion' => 'Eliminar lógicamente registros del catálogo comercial', 'modulo' => 'comercial'],
            ['clave' => 'checklist_entregables.ver', 'descripcion' => 'Ver checklist de entregables', 'modulo' => 'operacion'],
            ['clave' => 'checklist_entregables.crear', 'descripcion' => 'Crear checklist de entregables', 'modulo' => 'operacion'],
            ['clave' => 'checklist_entregables.editar', 'descripcion' => 'Editar revisión de checklist de entregables', 'modulo' => 'operacion'],
            ['clave' => 'escaneo_producto.ver', 'descripcion' => 'Consultar productos por escaneo y existencias por sucursal', 'modulo' => 'operacion'],
            ['clave' => 'inventario_base.ver', 'descripcion' => 'Ver inventario base', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.inicial', 'descripcion' => 'Registrar inventario inicial', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.entrada', 'descripcion' => 'Registrar entradas de inventario', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.salida', 'descripcion' => 'Registrar salidas de inventario', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.ajustar', 'descripcion' => 'Realizar ajustes de inventario', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.cancelar', 'descripcion' => 'Cancelar movimientos de inventario', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.corregir', 'descripcion' => 'Corregir movimientos de inventario', 'modulo' => 'inventario'],
            ['clave' => 'inventario_base.minimos', 'descripcion' => 'Gestionar mínimos de inventario', 'modulo' => 'inventario'],
            ['clave' => 'caja.ver', 'descripcion' => 'Ver cajas', 'modulo' => 'pos'],
            ['clave' => 'caja.crear', 'descripcion' => 'Crear cajas', 'modulo' => 'pos'],
            ['clave' => 'caja.editar', 'descripcion' => 'Editar cajas', 'modulo' => 'pos'],
            ['clave' => 'caja.inactivar', 'descripcion' => 'Activar/Inactivar cajas', 'modulo' => 'pos'],
            ['clave' => 'caja.eliminar', 'descripcion' => 'Eliminar cajas (lógico)', 'modulo' => 'pos'],
            ['clave' => 'pedido_piso.ver', 'descripcion' => 'Ver pedidos de piso', 'modulo' => 'pos'],
            ['clave' => 'pedido_piso.crear', 'descripcion' => 'Crear pedidos de piso', 'modulo' => 'pos'],
            ['clave' => 'cliente.ver', 'descripcion' => 'Ver clientes', 'modulo' => 'pos'],
            ['clave' => 'cliente.crear', 'descripcion' => 'Crear clientes', 'modulo' => 'pos'],
            ['clave' => 'cliente.editar', 'descripcion' => 'Editar clientes', 'modulo' => 'pos'],
            ['clave' => 'cliente.inactivar', 'descripcion' => 'Activar/Inactivar clientes', 'modulo' => 'pos'],
            ['clave' => 'cliente.eliminar', 'descripcion' => 'Eliminar clientes (lógico)', 'modulo' => 'pos'],
            ['clave' => 'pos.cancelar_venta', 'descripcion' => 'Cancelar ventas POS registradas', 'modulo' => 'pos'],
            ['clave' => 'pos.cambio_devolucion', 'descripcion' => 'Registrar cambios/devoluciones sin reembolso en POS', 'modulo' => 'pos'],
            ['clave' => 'pos.retiro_caja', 'descripcion' => 'Registrar retiros de caja en POS', 'modulo' => 'pos'],
            ['clave' => 'pos.gasto_caja', 'descripcion' => 'Registrar gastos de caja en POS', 'modulo' => 'pos'],
            ['clave' => 'pos.corte_caja', 'descripcion' => 'Autorizar cierres y cortes de caja en POS', 'modulo' => 'pos'],
            ['clave' => 'reportes.ventas.ver', 'descripcion' => 'Consultar reportes de ventas', 'modulo' => 'reportes'],
            ['clave' => 'reportes.caja.ver', 'descripcion' => 'Consultar reportes de caja', 'modulo' => 'reportes'],
            ['clave' => 'reportes.inventario.ver', 'descripcion' => 'Consultar reportes de inventario', 'modulo' => 'reportes'],
            ['clave' => 'reportes.exportar', 'descripcion' => 'Exportar reportes', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.ver', 'descripcion' => 'Consultar el reporte de comisiones', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.configurar', 'descripcion' => 'Configurar grupos, vendedores y periodos de comisión', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.calcular', 'descripcion' => 'Calcular comisiones por primera vez', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.recalcular', 'descripcion' => 'Recalcular periodos de comisión no cerrados', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.cerrar', 'descripcion' => 'Cerrar periodos de comisión', 'modulo' => 'reportes'],
            ['clave' => 'comisiones.exportar', 'descripcion' => 'Exportar el reporte de comisiones', 'modulo' => 'reportes'],
        ];

        foreach ($permisosBase as $permiso) {
            Permiso::query()->updateOrCreate(
                ['prm_clave' => $permiso['clave']],
                [
                    'prm_descripcion' => $permiso['descripcion'],
                    'prm_modulo' => $permiso['modulo'],
                    'prm_estatus' => 'activo',
                    'prm_deleted' => false,
                    'prm_deleted_at' => null,
                ]
            );
        }

        $sucursal = Sucursal::query()->updateOrCreate(
            ['scl_clave' => 'MATRIZ'],
            [
                'scl_nombre' => 'Casa Matriz',
                'scl_estatus' => 'activo',
                'scl_deleted' => false,
                'scl_deleted_at' => null,
            ]
        );

        $permisoIds = Permiso::query()->pluck('prm_id')->all();
        $permisosPorClave = Permiso::query()->pluck('prm_id', 'prm_clave');

        $rolesBase = [
            'Administrador' => [
                'descripcion' => 'Rol administrador con acceso completo a todos los módulos.',
                'permisos' => '*',
            ],
            'Administrador del Sistema' => [
                'descripcion' => 'Rol legacy con acceso completo para compatibilidad.',
                'permisos' => '*',
            ],
            'Supervisor' => [
                'descripcion' => 'Supervisa catálogos, checklists y consulta operativa.',
                'permisos' => [
                    'catalogo_comercial.ver',
                    'catalogo_comercial.crear',
                    'catalogo_comercial.editar',
                    'checklist_entregables.ver',
                    'checklist_entregables.crear',
                    'checklist_entregables.editar',
                    'sucursal.ver',
                    'almacen.ver',
                    'tipo_almacen.ver',
                    'escaneo_producto.ver',
                    'inventario_base.ver',
                    'inventario_base.inicial',
                    'inventario_base.entrada',
                    'inventario_base.salida',
                    'inventario_base.minimos',
                    'caja.ver',
                    'pedido_piso.ver',
                    'pedido_piso.crear',
                    'cliente.ver',
                    'cliente.crear',
                    'cliente.editar',
                    'pos.cancelar_venta',
                    'pos.cambio_devolucion',
                    'pos.retiro_caja',
                    'pos.gasto_caja',
                    'pos.corte_caja',
                ],
            ],
            'Cajero' => [
                'descripcion' => 'Perfil operativo para consulta de producto en punto de venta.',
                'permisos' => [
                    'catalogo_comercial.ver',
                    'escaneo_producto.ver',
                    'inventario_base.ver',
                    'caja.ver',
                    'pedido_piso.ver',
                    'cliente.ver',
                    'pos.cancelar_venta',
                    'pos.cambio_devolucion',
                    'pos.retiro_caja',
                    'pos.gasto_caja',
                ],
            ],
            'Vendedor piso' => [
                'descripcion' => 'Perfil de piso para consulta comercial y escaneo de producto.',
                'permisos' => [
                    'catalogo_comercial.ver',
                    'escaneo_producto.ver',
                    'inventario_base.ver',
                    'caja.ver',
                    'pedido_piso.ver',
                    'pedido_piso.crear',
                    'cliente.ver',
                    'cliente.crear',
                ],
            ],
        ];

        $rolesCreados = [];
        foreach ($rolesBase as $rolNombre => $configRol) {
            $rol = Rol::query()->updateOrCreate(
                ['rol_nombre' => $rolNombre],
                [
                    'rol_descripcion' => $configRol['descripcion'],
                    'rol_estatus' => 'activo',
                    'rol_deleted' => false,
                    'rol_deleted_at' => null,
                ]
            );

            $rolesCreados[$rolNombre] = $rol;
            $permisosAsignar = $configRol['permisos'] === '*'
                ? $permisoIds
                : collect($configRol['permisos'])
                    ->map(fn ($clave) => $permisosPorClave->get($clave))
                    ->filter()
                    ->values()
                    ->all();

            foreach ($permisosAsignar as $permisoId) {
                RolPermiso::query()->updateOrCreate(
                    ['rpm_rol_id' => $rol->rol_id, 'rpm_prm_id' => $permisoId],
                    [
                        'rpm_estatus' => 'activo',
                        'rpm_deleted' => false,
                        'rpm_deleted_at' => null,
                    ]
                );
            }
        }

        $usuarioAdmin = Usuario::query()->updateOrCreate(
            ['usr_usuario' => 'admin'],
            [
                'usr_nombre' => 'Administrador General',
                'usr_email' => 'admin@lasuriana.local',
                'usr_password' => Hash::make('12345678'),
                'usr_estatus' => 'activo',
                'usr_deleted' => false,
                'usr_deleted_at' => null,
            ]
        );

        UsuarioRol::query()->updateOrCreate(
            ['url_usr_id' => $usuarioAdmin->usr_id, 'url_rol_id' => $rolesCreados['Administrador']->rol_id],
            [
                'url_estatus' => 'activo',
                'url_deleted' => false,
                'url_deleted_at' => null,
            ]
        );

        UsuarioSucursal::query()->updateOrCreate(
            ['usc_usr_id' => $usuarioAdmin->usr_id, 'usc_scl_id' => $sucursal->scl_id],
            [
                'usc_es_predeterminada' => true,
                'usc_estatus' => 'activo',
                'usc_deleted' => false,
                'usc_deleted_at' => null,
            ]
        );
    }
}
