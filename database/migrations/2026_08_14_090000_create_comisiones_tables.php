<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comision_grupos_cgr', function (Blueprint $table) {
            $table->bigIncrements('cgr_id');
            $table->string('cgr_clave', 40);
            $table->string('cgr_nombre', 120);
            $table->decimal('cgr_incremento_minimo', 7, 2)->default(0);
            $table->decimal('cgr_incremento_maximo', 7, 2)->default(100);
            $table->string('cgr_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('cgr_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cgr_updated_by_usr_id')->nullable()->index();
            $table->boolean('cgr_deleted')->default(false)->index();
            $table->timestamp('cgr_deleted_at')->nullable()->index();
            $table->timestamp('cgr_created_at')->nullable();
            $table->timestamp('cgr_updated_at')->nullable();

            $table->unique(['cgr_clave', 'cgr_deleted'], 'uk_cgr_clave_activo');
        });

        Schema::create('tbl_comision_grupo_lineas_cgl', function (Blueprint $table) {
            $table->bigIncrements('cgl_id');
            $table->unsignedBigInteger('cgl_cgr_id');
            $table->unsignedBigInteger('cgl_lna_id');
            $table->timestamp('cgl_created_at')->nullable();
            $table->timestamp('cgl_updated_at')->nullable();

            $table->foreign('cgl_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->foreign('cgl_lna_id')->references('lna_id')->on('tbl_lineas_lna');
            $table->unique('cgl_lna_id', 'uk_cgl_linea');
            $table->unique(['cgl_cgr_id', 'cgl_lna_id'], 'uk_cgl_grupo_linea');
        });

        Schema::create('tbl_comision_vendedores_cve', function (Blueprint $table) {
            $table->bigIncrements('cve_id');
            $table->unsignedBigInteger('cve_usr_id');
            $table->unsignedBigInteger('cve_cgr_id');
            $table->string('cve_numero', 40);
            $table->string('cve_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('cve_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cve_updated_by_usr_id')->nullable()->index();
            $table->timestamp('cve_created_at')->nullable();
            $table->timestamp('cve_updated_at')->nullable();

            $table->foreign('cve_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('cve_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->unique('cve_usr_id', 'uk_cve_usuario');
            $table->unique('cve_numero', 'uk_cve_numero');
        });

        Schema::create('tbl_comision_periodos_cpe', function (Blueprint $table) {
            $table->bigIncrements('cpe_id');
            $table->unsignedBigInteger('cpe_scl_id');
            $table->date('cpe_periodo');
            $table->decimal('cpe_factor_comisionable', 7, 2)->default(33);
            $table->decimal('cpe_tasa_general', 7, 4)->default(0.9);
            $table->decimal('cpe_cumplimiento_minimo', 7, 2)->default(100);
            $table->string('cpe_estatus', 20)->default('borrador')->index();
            $table->timestamp('cpe_calculado_at')->nullable();
            $table->unsignedBigInteger('cpe_calculado_by_usr_id')->nullable()->index();
            $table->timestamp('cpe_cerrado_at')->nullable();
            $table->unsignedBigInteger('cpe_cerrado_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cpe_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('cpe_updated_by_usr_id')->nullable()->index();
            $table->timestamp('cpe_created_at')->nullable();
            $table->timestamp('cpe_updated_at')->nullable();

            $table->foreign('cpe_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('cpe_calculado_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('cpe_cerrado_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->unique(['cpe_scl_id', 'cpe_periodo'], 'uk_cpe_sucursal_periodo');
        });

        Schema::create('tbl_comision_periodo_almacenes_cpa', function (Blueprint $table) {
            $table->bigIncrements('cpa_id');
            $table->unsignedBigInteger('cpa_cpe_id');
            $table->unsignedBigInteger('cpa_alm_id');
            $table->timestamp('cpa_created_at')->nullable();
            $table->timestamp('cpa_updated_at')->nullable();

            $table->foreign('cpa_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('cpa_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->unique(['cpa_cpe_id', 'cpa_alm_id'], 'uk_cpa_periodo_almacen');
        });

        Schema::create('tbl_comision_periodo_grupos_cpg', function (Blueprint $table) {
            $table->bigIncrements('cpg_id');
            $table->unsignedBigInteger('cpg_cpe_id');
            $table->unsignedBigInteger('cpg_cgr_id');
            $table->decimal('cpg_vendedores_promedio', 8, 2);
            $table->decimal('cpg_incremento_meta', 7, 2);
            $table->decimal('cpg_ventas_grupo', 16, 2)->default(0);
            $table->decimal('cpg_ventas_sin_atencion', 16, 2)->default(0);
            $table->decimal('cpg_base_meta', 16, 2)->default(0);
            $table->decimal('cpg_meta_individual', 16, 2)->default(0);
            $table->timestamp('cpg_created_at')->nullable();
            $table->timestamp('cpg_updated_at')->nullable();

            $table->foreign('cpg_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('cpg_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->unique(['cpg_cpe_id', 'cpg_cgr_id'], 'uk_cpg_periodo_grupo');
        });

        Schema::create('tbl_comision_ajustes_vendedor_cav', function (Blueprint $table) {
            $table->bigIncrements('cav_id');
            $table->unsignedBigInteger('cav_cpe_id');
            $table->unsignedBigInteger('cav_cve_id');
            $table->decimal('cav_ajuste_tasa', 7, 4)->default(0);
            $table->decimal('cav_tasa_final', 7, 4)->nullable();
            $table->decimal('cav_bono', 14, 2)->default(0);
            $table->string('cav_motivo', 500)->nullable();
            $table->timestamp('cav_created_at')->nullable();
            $table->timestamp('cav_updated_at')->nullable();

            $table->foreign('cav_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('cav_cve_id')->references('cve_id')->on('tbl_comision_vendedores_cve');
            $table->unique(['cav_cpe_id', 'cav_cve_id'], 'uk_cav_periodo_vendedor');
        });

        Schema::create('tbl_comision_resultados_crs', function (Blueprint $table) {
            $table->bigIncrements('crs_id');
            $table->unsignedBigInteger('crs_cpe_id');
            $table->unsignedBigInteger('crs_cve_id');
            $table->unsignedBigInteger('crs_cgr_id');
            $table->string('crs_numero_vendedor', 40);
            $table->string('crs_nombre_vendedor', 160);
            $table->string('crs_grupo_nombre', 120);
            $table->decimal('crs_ventas_totales', 16, 2)->default(0);
            $table->decimal('crs_meta', 16, 2)->default(0);
            $table->decimal('crs_cumplimiento', 9, 2)->default(0);
            $table->decimal('crs_factor_comisionable', 7, 2)->default(0);
            $table->decimal('crs_base_comisionable', 16, 2)->default(0);
            $table->decimal('crs_tasa_general', 7, 4)->default(0);
            $table->decimal('crs_ajuste_tasa', 7, 4)->default(0);
            $table->decimal('crs_tasa_final', 7, 4)->default(0);
            $table->decimal('crs_comision', 16, 2)->default(0);
            $table->decimal('crs_bono', 14, 2)->default(0);
            $table->decimal('crs_total_pagar', 16, 2)->default(0);
            $table->string('crs_observaciones', 500)->nullable();
            $table->timestamp('crs_created_at')->nullable();
            $table->timestamp('crs_updated_at')->nullable();

            $table->foreign('crs_cpe_id')->references('cpe_id')->on('tbl_comision_periodos_cpe');
            $table->foreign('crs_cve_id')->references('cve_id')->on('tbl_comision_vendedores_cve');
            $table->foreign('crs_cgr_id')->references('cgr_id')->on('tbl_comision_grupos_cgr');
            $table->unique(['crs_cpe_id', 'crs_cve_id'], 'uk_crs_periodo_vendedor');
        });

        $ahora = now();
        DB::table('tbl_comision_grupos_cgr')->insert([
            [
                'cgr_clave' => 'ROPA',
                'cgr_nombre' => 'Ropa',
                'cgr_incremento_minimo' => 8,
                'cgr_incremento_maximo' => 15,
                'cgr_estatus' => 'activo',
                'cgr_deleted' => false,
                'cgr_created_at' => $ahora,
                'cgr_updated_at' => $ahora,
            ],
            [
                'cgr_clave' => 'TELAS',
                'cgr_nombre' => 'Telas',
                'cgr_incremento_minimo' => 5,
                'cgr_incremento_maximo' => 12,
                'cgr_estatus' => 'activo',
                'cgr_deleted' => false,
                'cgr_created_at' => $ahora,
                'cgr_updated_at' => $ahora,
            ],
        ]);

        $permisos = [
            ['comisiones.ver', 'Consultar el reporte de comisiones'],
            ['comisiones.configurar', 'Configurar grupos, vendedores y periodos de comisión'],
            ['comisiones.calcular', 'Calcular comisiones por primera vez'],
            ['comisiones.recalcular', 'Recalcular periodos de comisión no cerrados'],
            ['comisiones.cerrar', 'Cerrar periodos de comisión'],
            ['comisiones.exportar', 'Exportar el reporte de comisiones'],
        ];

        foreach ($permisos as [$clave, $descripcion]) {
            DB::table('tbl_permisos_prm')->updateOrInsert(
                ['prm_clave' => $clave],
                [
                    'prm_descripcion' => $descripcion,
                    'prm_modulo' => 'reportes',
                    'prm_estatus' => 'activo',
                    'prm_deleted' => false,
                    'prm_deleted_at' => null,
                    'prm_updated_at' => $ahora,
                ]
            );
        }

        $permisoIds = DB::table('tbl_permisos_prm')->whereIn('prm_clave', collect($permisos)->pluck(0))->pluck('prm_id');
        $rolIds = DB::table('tbl_roles_rol')
            ->whereRaw("LOWER(rol_nombre) IN ('administrador', 'administrador del sistema')")
            ->pluck('rol_id');
        foreach ($rolIds as $rolId) {
            foreach ($permisoIds as $permisoId) {
                DB::table('tbl_rol_permisos_rpm')->updateOrInsert(
                    ['rpm_rol_id' => $rolId, 'rpm_prm_id' => $permisoId, 'rpm_deleted' => false],
                    ['rpm_estatus' => 'activo', 'rpm_deleted_at' => null, 'rpm_updated_at' => $ahora]
                );
            }
        }
    }

    public function down(): void
    {
        $claves = ['comisiones.ver', 'comisiones.configurar', 'comisiones.calcular', 'comisiones.recalcular', 'comisiones.cerrar', 'comisiones.exportar'];
        $permisoIds = DB::table('tbl_permisos_prm')->whereIn('prm_clave', $claves)->pluck('prm_id');
        DB::table('tbl_rol_permisos_rpm')->whereIn('rpm_prm_id', $permisoIds)->delete();
        DB::table('tbl_permisos_prm')->whereIn('prm_id', $permisoIds)->delete();

        Schema::dropIfExists('tbl_comision_resultados_crs');
        Schema::dropIfExists('tbl_comision_ajustes_vendedor_cav');
        Schema::dropIfExists('tbl_comision_periodo_grupos_cpg');
        Schema::dropIfExists('tbl_comision_periodo_almacenes_cpa');
        Schema::dropIfExists('tbl_comision_periodos_cpe');
        Schema::dropIfExists('tbl_comision_vendedores_cve');
        Schema::dropIfExists('tbl_comision_grupo_lineas_cgl');
        Schema::dropIfExists('tbl_comision_grupos_cgr');
    }
};
