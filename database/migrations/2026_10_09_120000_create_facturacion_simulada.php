<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_almacenes_alm', function (Blueprint $table) {
            $table->boolean('alm_permite_facturar')->default(false);
        });

        Schema::create('tbl_facturacion_fac', function (Blueprint $table) {
            $table->bigIncrements('fac_id');
            $table->unsignedBigInteger('fac_psv_id')->unique();
            $table->unsignedBigInteger('fac_alm_id')->nullable();
            $table->string('fac_estado', 20)->default('ajustada');
            $table->string('fac_folio', 50)->nullable()->unique();
            $table->json('fac_original');
            $table->json('fac_partidas');
            $table->decimal('fac_total_original', 14, 2);
            $table->decimal('fac_total', 14, 2);
            $table->decimal('fac_diferencia', 14, 2);
            $table->string('fac_inventario_estado', 30)->default('pendiente_definicion');
            $table->text('fac_notas')->nullable();
            $table->unsignedInteger('fac_version')->default(1);
            $table->unsignedBigInteger('fac_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('fac_updated_by_usr_id')->nullable();
            $table->timestamp('fac_emitida_at')->nullable();
            $table->timestamp('fac_created_at')->nullable();
            $table->timestamp('fac_updated_at')->nullable();
            $table->foreign('fac_psv_id')->references('psv_id')->on('tbl_pos_ventas_psv');
            $table->foreign('fac_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
        });

        foreach (['ver' => 'Consultar facturación', 'ajustar' => 'Ajustar tickets para facturación', 'emitir' => 'Simular facturas'] as $accion => $descripcion) {
            $permisoId = DB::table('tbl_permisos_prm')->insertGetId([
                'prm_clave' => 'facturacion.'.$accion, 'prm_descripcion' => $descripcion,
                'prm_modulo' => 'facturacion', 'prm_estatus' => 'activo', 'prm_deleted' => false,
                'prm_created_at' => now(), 'prm_updated_at' => now(),
            ]);
            foreach (DB::table('tbl_roles_rol')->whereIn('rol_nombre', ['Administrador', 'Administrador del Sistema'])->pluck('rol_id') as $rolId) {
                DB::table('tbl_rol_permisos_rpm')->insert([
                    'rpm_rol_id' => $rolId, 'rpm_prm_id' => $permisoId,
                    'rpm_estatus' => 'activo', 'rpm_deleted' => false,
                    'rpm_created_at' => now(), 'rpm_updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('tbl_permisos_prm')->whereIn('prm_clave', ['facturacion.ver', 'facturacion.ajustar', 'facturacion.emitir'])->pluck('prm_id');
        DB::table('tbl_rol_permisos_rpm')->whereIn('rpm_prm_id', $ids)->delete();
        DB::table('tbl_permisos_prm')->whereIn('prm_id', $ids)->delete();
        Schema::dropIfExists('tbl_facturacion_fac');
        Schema::table('tbl_almacenes_alm', fn (Blueprint $table) => $table->dropColumn('alm_permite_facturar'));
    }
};
