<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_comision_v2_departamentos_cmd', function (Blueprint $table) {
            $table->bigIncrements('cmd_id');
            $table->string('cmd_clave', 40);
            $table->string('cmd_nombre', 120);
            $table->string('cmd_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('cmd_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('cmd_updated_by_usr_id')->nullable();
            $table->boolean('cmd_deleted')->default(false)->index();
            $table->timestamp('cmd_deleted_at')->nullable();
            $table->timestamp('cmd_created_at')->nullable();
            $table->timestamp('cmd_updated_at')->nullable();
            $table->unique(['cmd_clave', 'cmd_deleted'], 'uk_cmd_clave');
        });

        Schema::create('tbl_comision_v2_departamento_lineas_cdl', function (Blueprint $table) {
            $table->bigIncrements('cdl_id');
            $table->unsignedBigInteger('cdl_cmd_id');
            $table->unsignedBigInteger('cdl_lna_id');
            $table->timestamp('cdl_created_at')->nullable();
            $table->timestamp('cdl_updated_at')->nullable();
            $table->foreign('cdl_cmd_id', 'fk_cdl_cmd')->references('cmd_id')->on('tbl_comision_v2_departamentos_cmd');
            $table->foreign('cdl_lna_id', 'fk_cdl_lna')->references('lna_id')->on('tbl_lineas_lna');
            $table->unique('cdl_lna_id', 'uk_cdl_linea');
        });

        Schema::create('tbl_comision_v2_periodos_cmp', function (Blueprint $table) {
            $table->bigIncrements('cmp_id');
            $table->unsignedBigInteger('cmp_scl_id');
            $table->date('cmp_periodo');
            $table->decimal('cmp_factor_comisionable', 7, 2)->default(33);
            $table->string('cmp_estatus', 20)->default('borrador')->index();
            $table->text('cmp_ultimo_motivo_cambio')->nullable();
            $table->timestamp('cmp_aprobado_at')->nullable();
            $table->unsignedBigInteger('cmp_aprobado_by_usr_id')->nullable();
            $table->timestamp('cmp_cerrado_at')->nullable();
            $table->unsignedBigInteger('cmp_cerrado_by_usr_id')->nullable();
            $table->unsignedBigInteger('cmp_created_by_usr_id')->nullable();
            $table->unsignedBigInteger('cmp_updated_by_usr_id')->nullable();
            $table->timestamp('cmp_created_at')->nullable();
            $table->timestamp('cmp_updated_at')->nullable();
            $table->foreign('cmp_scl_id', 'fk_cmp_scl')->references('scl_id')->on('tbl_sucursales_scl');
            $table->unique(['cmp_scl_id', 'cmp_periodo'], 'uk_cmp_sucursal_periodo');
        });

        Schema::create('tbl_comision_v2_periodo_almacenes_cma', function (Blueprint $table) {
            $table->bigIncrements('cma_id');
            $table->unsignedBigInteger('cma_cmp_id');
            $table->unsignedBigInteger('cma_alm_id');
            $table->timestamp('cma_created_at')->nullable();
            $table->timestamp('cma_updated_at')->nullable();
            $table->foreign('cma_cmp_id', 'fk_cma_cmp')->references('cmp_id')->on('tbl_comision_v2_periodos_cmp')->cascadeOnDelete();
            $table->foreign('cma_alm_id', 'fk_cma_alm')->references('alm_id')->on('tbl_almacenes_alm');
            $table->unique(['cma_cmp_id', 'cma_alm_id'], 'uk_cma_periodo_almacen');
        });

        Schema::create('tbl_comision_v2_periodo_departamentos_cpd', function (Blueprint $table) {
            $table->bigIncrements('cpd_id');
            $table->unsignedBigInteger('cpd_cmp_id');
            $table->unsignedBigInteger('cpd_cmd_id');
            $table->string('cpd_departamento_nombre', 120);
            $table->string('cpd_origen_meta', 20)->default('manual');
            $table->date('cpd_periodo_referencia')->nullable();
            $table->decimal('cpd_ventas_historicas', 16, 2)->default(0);
            $table->decimal('cpd_autoservicio_historico', 16, 2)->default(0);
            $table->decimal('cpd_base_historica', 16, 2)->default(0);
            $table->unsignedInteger('cpd_vendedores_congelados')->default(0);
            $table->decimal('cpd_incremento_meta', 7, 2)->default(0);
            $table->decimal('cpd_meta_sugerida', 16, 2)->nullable();
            $table->decimal('cpd_meta_comun', 16, 2)->nullable();
            $table->timestamp('cpd_created_at')->nullable();
            $table->timestamp('cpd_updated_at')->nullable();
            $table->foreign('cpd_cmp_id', 'fk_cpd_cmp')->references('cmp_id')->on('tbl_comision_v2_periodos_cmp')->cascadeOnDelete();
            $table->foreign('cpd_cmd_id', 'fk_cpd_cmd')->references('cmd_id')->on('tbl_comision_v2_departamentos_cmd');
            $table->unique(['cpd_cmp_id', 'cpd_cmd_id'], 'uk_cpd_periodo_depto');
        });

        Schema::create('tbl_comision_v2_periodo_lineas_cml', function (Blueprint $table) {
            $table->bigIncrements('cml_id');
            $table->unsignedBigInteger('cml_cmp_id');
            $table->unsignedBigInteger('cml_cpd_id');
            $table->unsignedBigInteger('cml_lna_id');
            $table->string('cml_linea_nombre', 160);
            $table->timestamp('cml_created_at')->nullable();
            $table->timestamp('cml_updated_at')->nullable();
            $table->foreign('cml_cmp_id', 'fk_cml_cmp')->references('cmp_id')->on('tbl_comision_v2_periodos_cmp')->cascadeOnDelete();
            $table->foreign('cml_cpd_id', 'fk_cml_cpd')->references('cpd_id')->on('tbl_comision_v2_periodo_departamentos_cpd')->cascadeOnDelete();
            $table->foreign('cml_lna_id', 'fk_cml_lna')->references('lna_id')->on('tbl_lineas_lna');
            $table->unique(['cml_cmp_id', 'cml_lna_id'], 'uk_cml_periodo_linea');
        });

        Schema::create('tbl_comision_v2_participantes_cpt', function (Blueprint $table) {
            $table->bigIncrements('cpt_id');
            $table->unsignedBigInteger('cpt_cmp_id');
            $table->unsignedBigInteger('cpt_cpd_id');
            $table->unsignedBigInteger('cpt_usr_id');
            $table->string('cpt_numero_vendedor', 40);
            $table->string('cpt_nombre_vendedor', 160);
            $table->decimal('cpt_meta_individual', 16, 2);
            $table->decimal('cpt_tasa_comision', 5, 4)->default(0.9);
            $table->string('cpt_motivo_ajuste', 500)->nullable();
            $table->timestamp('cpt_created_at')->nullable();
            $table->timestamp('cpt_updated_at')->nullable();
            $table->foreign('cpt_cmp_id', 'fk_cpt_cmp')->references('cmp_id')->on('tbl_comision_v2_periodos_cmp')->cascadeOnDelete();
            $table->foreign('cpt_cpd_id', 'fk_cpt_cpd')->references('cpd_id')->on('tbl_comision_v2_periodo_departamentos_cpd')->cascadeOnDelete();
            $table->foreign('cpt_usr_id', 'fk_cpt_usr')->references('usr_id')->on('tbl_usuarios_usr');
            $table->unique(['cpt_cmp_id', 'cpt_usr_id'], 'uk_cpt_periodo_usuario');
            $table->unique(['cpt_cmp_id', 'cpt_numero_vendedor'], 'uk_cpt_periodo_numero');
        });

        Schema::create('tbl_comision_v2_resultados_cmr', function (Blueprint $table) {
            $table->bigIncrements('cmr_id');
            $table->unsignedBigInteger('cmr_cmp_id');
            $table->unsignedBigInteger('cmr_cpt_id');
            $table->unsignedBigInteger('cmr_usr_id');
            $table->string('cmr_numero_vendedor', 40);
            $table->string('cmr_nombre_vendedor', 160);
            $table->string('cmr_departamento_nombre', 120);
            $table->decimal('cmr_ventas_netas', 16, 2)->default(0);
            $table->decimal('cmr_meta_individual', 16, 2);
            $table->decimal('cmr_cumplimiento', 9, 2)->default(0);
            $table->decimal('cmr_factor_comisionable', 7, 2)->default(33);
            $table->decimal('cmr_base_comisionable', 16, 2)->default(0);
            $table->decimal('cmr_tasa_comision', 5, 4)->default(0);
            $table->decimal('cmr_comision', 16, 2)->default(0);
            $table->timestamp('cmr_created_at')->nullable();
            $table->timestamp('cmr_updated_at')->nullable();
            $table->foreign('cmr_cmp_id', 'fk_cmr_cmp')->references('cmp_id')->on('tbl_comision_v2_periodos_cmp')->cascadeOnDelete();
            $table->foreign('cmr_cpt_id', 'fk_cmr_cpt')->references('cpt_id')->on('tbl_comision_v2_participantes_cpt');
            $table->foreign('cmr_usr_id', 'fk_cmr_usr')->references('usr_id')->on('tbl_usuarios_usr');
            $table->unique(['cmr_cmp_id', 'cmr_usr_id'], 'uk_cmr_periodo_usuario');
        });

        Schema::create('tbl_comision_v2_resultado_detalles_crx', function (Blueprint $table) {
            $table->bigIncrements('crx_id');
            $table->unsignedBigInteger('crx_cmr_id');
            $table->unsignedBigInteger('crx_alm_id')->nullable();
            $table->string('crx_almacen_nombre', 160);
            $table->unsignedBigInteger('crx_lna_id')->nullable();
            $table->string('crx_linea_nombre', 160);
            $table->decimal('crx_venta_bruta', 16, 2)->default(0);
            $table->decimal('crx_descuentos', 16, 2)->default(0);
            $table->decimal('crx_devoluciones', 16, 2)->default(0);
            $table->decimal('crx_venta_neta', 16, 2)->default(0);
            $table->timestamp('crx_created_at')->nullable();
            $table->timestamp('crx_updated_at')->nullable();
            $table->foreign('crx_cmr_id', 'fk_crx_cmr')->references('cmr_id')->on('tbl_comision_v2_resultados_cmr')->cascadeOnDelete();
            $table->index(['crx_cmr_id', 'crx_alm_id'], 'ix_crx_resultado_almacen');
        });

        $ahora = now();
        foreach ([['ROPA', 'Ropa'], ['TELAS', 'Telas']] as [$clave, $nombre]) {
            $departamentoId = DB::table('tbl_comision_v2_departamentos_cmd')->insertGetId([
                'cmd_clave' => $clave,
                'cmd_nombre' => $nombre,
                'cmd_estatus' => 'activo',
                'cmd_deleted' => false,
                'cmd_created_at' => $ahora,
                'cmd_updated_at' => $ahora,
            ]);
            $grupoId = DB::table('tbl_comision_grupos_cgr')->where('cgr_clave', $clave)->value('cgr_id');
            if ($grupoId) {
                $lineas = DB::table('tbl_comision_grupo_lineas_cgl')->where('cgl_cgr_id', $grupoId)->pluck('cgl_lna_id');
                foreach ($lineas as $lineaId) {
                    DB::table('tbl_comision_v2_departamento_lineas_cdl')->insert([
                        'cdl_cmd_id' => $departamentoId,
                        'cdl_lna_id' => $lineaId,
                        'cdl_created_at' => $ahora,
                        'cdl_updated_at' => $ahora,
                    ]);
                }
            }
        }

        $permisosAdmin = [
            ['comisiones.aprobar', 'Aprobar metas y equipos mensuales'],
            ['comisiones.estimar', 'Consultar avance y comisión estimada'],
            ['comisiones.historial', 'Consultar periodos históricos de comisiones'],
        ];
        foreach ($permisosAdmin as [$clave, $descripcion]) {
            DB::table('tbl_permisos_prm')->updateOrInsert(['prm_clave' => $clave], [
                'prm_descripcion' => $descripcion,
                'prm_modulo' => 'reportes',
                'prm_estatus' => 'activo',
                'prm_deleted' => false,
                'prm_deleted_at' => null,
                'prm_updated_at' => $ahora,
            ]);
        }
        DB::table('tbl_permisos_prm')->updateOrInsert(['prm_clave' => 'comisiones.avance.propio'], [
            'prm_descripcion' => 'Consultar únicamente el avance de comisión propio',
            'prm_modulo' => 'reportes',
            'prm_estatus' => 'activo',
            'prm_deleted' => false,
            'prm_deleted_at' => null,
            'prm_updated_at' => $ahora,
        ]);

        $adminIds = DB::table('tbl_roles_rol')->whereRaw("LOWER(rol_nombre) IN ('administrador', 'administrador del sistema')")->pluck('rol_id');
        $adminPermissionIds = DB::table('tbl_permisos_prm')->whereIn('prm_clave', collect($permisosAdmin)->pluck(0))->pluck('prm_id');
        foreach ($adminIds as $rolId) {
            foreach ($adminPermissionIds as $permisoId) {
                DB::table('tbl_rol_permisos_rpm')->updateOrInsert(
                    ['rpm_rol_id' => $rolId, 'rpm_prm_id' => $permisoId, 'rpm_deleted' => false],
                    ['rpm_estatus' => 'activo', 'rpm_deleted_at' => null, 'rpm_updated_at' => $ahora]
                );
            }
        }
        $avanceId = DB::table('tbl_permisos_prm')->where('prm_clave', 'comisiones.avance.propio')->value('prm_id');
        foreach (DB::table('tbl_roles_rol')->where('rol_estatus', 'activo')->where('rol_deleted', false)->pluck('rol_id') as $rolId) {
            DB::table('tbl_rol_permisos_rpm')->updateOrInsert(
                ['rpm_rol_id' => $rolId, 'rpm_prm_id' => $avanceId, 'rpm_deleted' => false],
                ['rpm_estatus' => 'activo', 'rpm_deleted_at' => null, 'rpm_updated_at' => $ahora]
            );
        }
    }

    public function down(): void
    {
        foreach (['comisiones.aprobar', 'comisiones.estimar', 'comisiones.historial', 'comisiones.avance.propio'] as $clave) {
            $id = DB::table('tbl_permisos_prm')->where('prm_clave', $clave)->value('prm_id');
            if ($id) {
                DB::table('tbl_rol_permisos_rpm')->where('rpm_prm_id', $id)->delete();
                DB::table('tbl_permisos_prm')->where('prm_id', $id)->delete();
            }
        }
        Schema::dropIfExists('tbl_comision_v2_resultado_detalles_crx');
        Schema::dropIfExists('tbl_comision_v2_resultados_cmr');
        Schema::dropIfExists('tbl_comision_v2_participantes_cpt');
        Schema::dropIfExists('tbl_comision_v2_periodo_lineas_cml');
        Schema::dropIfExists('tbl_comision_v2_periodo_departamentos_cpd');
        Schema::dropIfExists('tbl_comision_v2_periodo_almacenes_cma');
        Schema::dropIfExists('tbl_comision_v2_periodos_cmp');
        Schema::dropIfExists('tbl_comision_v2_departamento_lineas_cdl');
        Schema::dropIfExists('tbl_comision_v2_departamentos_cmd');
    }
};
