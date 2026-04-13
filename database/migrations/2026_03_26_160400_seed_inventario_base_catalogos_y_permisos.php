<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $tiposMovimiento = [
            ['tmi_clave' => 'inventario.entrada', 'tmi_nombre' => 'Entrada de inventario', 'tmi_naturaleza' => 'entrada', 'tmi_clase' => 'entrada'],
            ['tmi_clave' => 'inventario.salida', 'tmi_nombre' => 'Salida de inventario', 'tmi_naturaleza' => 'salida', 'tmi_clase' => 'salida'],
            ['tmi_clave' => 'inventario.ajuste', 'tmi_nombre' => 'Ajuste de inventario', 'tmi_naturaleza' => 'salida', 'tmi_clase' => 'ajuste'],
            ['tmi_clave' => 'inventario.traspaso', 'tmi_nombre' => 'Traspaso de inventario', 'tmi_naturaleza' => 'salida', 'tmi_clase' => 'traspaso'],
        ];

        foreach ($tiposMovimiento as $row) {
            $registro = DB::table('tbl_tipos_movimiento_inventario_tmi')
                ->where('tmi_clave', $row['tmi_clave'])
                ->first();

            if ($registro) {
                DB::table('tbl_tipos_movimiento_inventario_tmi')
                    ->where('tmi_id', $registro->tmi_id)
                    ->update([
                        'tmi_nombre' => $row['tmi_nombre'],
                        'tmi_naturaleza' => $row['tmi_naturaleza'],
                        'tmi_clase' => $row['tmi_clase'],
                        'tmi_estatus' => 'activo',
                        'tmi_deleted' => false,
                        'tmi_deleted_at' => null,
                        'tmi_updated_at' => $ahora,
                    ]);
            } else {
                DB::table('tbl_tipos_movimiento_inventario_tmi')->insert([
                    'tmi_clave' => $row['tmi_clave'],
                    'tmi_nombre' => $row['tmi_nombre'],
                    'tmi_naturaleza' => $row['tmi_naturaleza'],
                    'tmi_clase' => $row['tmi_clase'],
                    'tmi_estatus' => 'activo',
                    'tmi_deleted' => false,
                    'tmi_created_at' => $ahora,
                    'tmi_updated_at' => $ahora,
                ]);
            }
        }

        $permisos = [
            ['clave' => 'inventario_base.ver', 'descripcion' => 'Ver inventario base'],
            ['clave' => 'inventario_base.inicial', 'descripcion' => 'Registrar inventario inicial'],
            ['clave' => 'inventario_base.entrada', 'descripcion' => 'Registrar entradas de inventario'],
            ['clave' => 'inventario_base.salida', 'descripcion' => 'Registrar salidas de inventario'],
            ['clave' => 'inventario_base.ajustar', 'descripcion' => 'Realizar ajustes de inventario'],
            ['clave' => 'inventario_base.cancelar', 'descripcion' => 'Cancelar movimientos de inventario'],
            ['clave' => 'inventario_base.corregir', 'descripcion' => 'Corregir movimientos de inventario'],
            ['clave' => 'inventario_base.minimos', 'descripcion' => 'Gestionar mínimos de inventario'],
        ];

        foreach ($permisos as $permiso) {
            $existe = DB::table('tbl_permisos_prm')->where('prm_clave', $permiso['clave'])->first();

            if ($existe) {
                DB::table('tbl_permisos_prm')
                    ->where('prm_id', $existe->prm_id)
                    ->update([
                        'prm_descripcion' => $permiso['descripcion'],
                        'prm_modulo' => 'inventario',
                        'prm_estatus' => 'activo',
                        'prm_deleted' => false,
                        'prm_deleted_at' => null,
                        'prm_updated_at' => $ahora,
                    ]);
            } else {
                DB::table('tbl_permisos_prm')->insert([
                    'prm_clave' => $permiso['clave'],
                    'prm_descripcion' => $permiso['descripcion'],
                    'prm_modulo' => 'inventario',
                    'prm_estatus' => 'activo',
                    'prm_deleted' => false,
                    'prm_created_at' => $ahora,
                    'prm_updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('tbl_tipos_movimiento_inventario_tmi')
            ->whereIn('tmi_clave', [
                'inventario.entrada',
                'inventario.salida',
                'inventario.ajuste',
                'inventario.traspaso',
            ])
            ->delete();

        DB::table('tbl_permisos_prm')
            ->whereIn('prm_clave', [
                'inventario_base.ver',
                'inventario_base.inicial',
                'inventario_base.entrada',
                'inventario_base.salida',
                'inventario_base.ajustar',
                'inventario_base.cancelar',
                'inventario_base.corregir',
                'inventario_base.minimos',
            ])
            ->delete();
    }
};
