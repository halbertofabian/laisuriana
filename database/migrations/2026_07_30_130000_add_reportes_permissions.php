<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            ['reportes.ventas.ver', 'Consultar reportes de ventas', 'reportes'], ['reportes.caja.ver', 'Consultar reportes de caja', 'reportes'], ['reportes.inventario.ver', 'Consultar reportes de inventario', 'reportes'],
            ['reportes.exportar', 'Exportar reportes', 'reportes'],
        ] as [$clave, $descripcion, $modulo]) DB::table('tbl_permisos_prm')->updateOrInsert(['prm_clave'=>$clave], ['prm_descripcion'=>$descripcion, 'prm_modulo'=>$modulo, 'prm_estatus'=>'activo', 'prm_deleted'=>false, 'prm_deleted_at'=>null]);
        $ids = DB::table('tbl_permisos_prm')->whereIn('prm_clave', ['reportes.ventas.ver','reportes.caja.ver','reportes.inventario.ver','reportes.exportar'])->pluck('prm_id');
        $roles = DB::table('tbl_roles_rol')->whereIn('rol_nombre', ['Administrador', 'Administrador del Sistema'])->pluck('rol_id');
        foreach ($roles as $rolId) foreach ($ids as $permisoId) DB::table('tbl_rol_permisos_rpm')->updateOrInsert(['rpm_rol_id'=>$rolId, 'rpm_prm_id'=>$permisoId, 'rpm_deleted'=>false], ['rpm_estatus'=>'activo', 'rpm_deleted_at'=>null]);
    }
    public function down(): void { DB::table('tbl_permisos_prm')->whereIn('prm_clave', ['reportes.ventas.ver','reportes.caja.ver','reportes.inventario.ver','reportes.exportar'])->delete(); }
};
