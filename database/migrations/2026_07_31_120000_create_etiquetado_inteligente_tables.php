<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_etiqueta_formatos_etf', function (Blueprint $table) {
            $table->bigIncrements('etf_id');
            $table->string('etf_nombre', 120);
            $table->text('etf_descripcion')->nullable();
            $table->decimal('etf_ancho_mm', 8, 2);
            $table->decimal('etf_alto_mm', 8, 2);
            $table->string('etf_orientacion', 12)->default('auto');
            $table->decimal('etf_margen_izq_mm', 7, 2)->default(0);
            $table->decimal('etf_margen_der_mm', 7, 2)->default(0);
            $table->decimal('etf_margen_sup_mm', 7, 2)->default(0);
            $table->decimal('etf_margen_inf_mm', 7, 2)->default(0);
            $table->string('etf_tipo_salida', 24)->default('termica');
            $table->unsignedSmallInteger('etf_columnas')->default(1);
            $table->unsignedSmallInteger('etf_filas')->default(1);
            $table->decimal('etf_separacion_h_mm', 7, 2)->default(0);
            $table->decimal('etf_separacion_v_mm', 7, 2)->default(0);
            $table->string('etf_compatibilidad_impresora', 120)->nullable();
            $table->string('etf_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('etf_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('etf_updated_by_usr_id')->nullable()->index();
            $table->boolean('etf_deleted')->default(false)->index();
            $table->timestamp('etf_deleted_at')->nullable()->index();
            $table->timestamp('etf_created_at')->nullable();
            $table->timestamp('etf_updated_at')->nullable();
            $table->unique(['etf_nombre', 'etf_deleted'], 'uk_etf_nombre_activo');
        });

        Schema::create('tbl_etiqueta_plantillas_etp', function (Blueprint $table) {
            $table->bigIncrements('etp_id');
            $table->string('etp_nombre', 120);
            $table->text('etp_descripcion')->nullable();
            $table->json('etp_campos')->nullable();
            $table->string('etp_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('etp_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('etp_updated_by_usr_id')->nullable()->index();
            $table->boolean('etp_deleted')->default(false)->index();
            $table->timestamp('etp_deleted_at')->nullable()->index();
            $table->timestamp('etp_created_at')->nullable();
            $table->timestamp('etp_updated_at')->nullable();
            $table->unique(['etp_nombre', 'etp_deleted'], 'uk_etp_nombre_activo');
        });

        Schema::create('tbl_etiqueta_linea_config_elc', function (Blueprint $table) {
            $table->bigIncrements('elc_id');
            $table->unsignedBigInteger('elc_lna_id');
            $table->unsignedBigInteger('elc_etf_id');
            $table->unsignedBigInteger('elc_etp_id');
            $table->string('elc_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('elc_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('elc_updated_by_usr_id')->nullable()->index();
            $table->boolean('elc_deleted')->default(false)->index();
            $table->timestamp('elc_deleted_at')->nullable()->index();
            $table->timestamp('elc_created_at')->nullable();
            $table->timestamp('elc_updated_at')->nullable();
            $table->unique(['elc_lna_id', 'elc_deleted'], 'uk_elc_linea_activa');
            $table->foreign('elc_lna_id')->references('lna_id')->on('tbl_lineas_lna');
            $table->foreign('elc_etf_id')->references('etf_id')->on('tbl_etiqueta_formatos_etf');
            $table->foreign('elc_etp_id')->references('etp_id')->on('tbl_etiqueta_plantillas_etp');
        });

        Schema::create('tbl_etiqueta_unidad_reglas_eur', function (Blueprint $table) {
            $table->bigIncrements('eur_id');
            $table->unsignedBigInteger('eur_umd_id');
            $table->string('eur_regla', 32);
            $table->string('eur_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('eur_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('eur_updated_by_usr_id')->nullable()->index();
            $table->boolean('eur_deleted')->default(false)->index();
            $table->timestamp('eur_deleted_at')->nullable()->index();
            $table->timestamp('eur_created_at')->nullable();
            $table->timestamp('eur_updated_at')->nullable();
            $table->unique(['eur_umd_id', 'eur_deleted'], 'uk_eur_unidad_activa');
            $table->foreign('eur_umd_id')->references('umd_id')->on('tbl_unidades_medida_umd');
        });

        Schema::create('tbl_etiqueta_impresiones_eim', function (Blueprint $table) {
            $table->bigIncrements('eim_id');
            $table->unsignedBigInteger('eim_rme_id');
            $table->unsignedBigInteger('eim_usuario_id')->nullable()->index();
            $table->unsignedBigInteger('eim_reimpresion_de_eim_id')->nullable()->index();
            $table->string('eim_modo', 24);
            $table->string('eim_estatus', 20)->default('pendiente')->index();
            $table->unsignedInteger('eim_total_etiquetas')->default(0);
            $table->unsignedInteger('eim_total_productos')->default(0);
            $table->json('eim_resumen')->nullable();
            $table->text('eim_error')->nullable();
            $table->timestamp('eim_generado_at')->nullable();
            $table->timestamp('eim_created_at')->nullable();
            $table->timestamp('eim_updated_at')->nullable();
            $table->foreign('eim_rme_id')->references('rme_id')->on('tbl_recepciones_mercancia_rme');
            $table->foreign('eim_usuario_id')->references('usr_id')->on('tbl_usuarios_usr');
        });

        Schema::create('tbl_etiqueta_impresion_archivos_eia', function (Blueprint $table) {
            $table->bigIncrements('eia_id');
            $table->unsignedBigInteger('eia_eim_id');
            $table->unsignedBigInteger('eia_etf_id')->nullable();
            $table->string('eia_nombre', 180);
            $table->string('eia_path', 255);
            $table->string('eia_mime', 80)->default('application/pdf');
            $table->unsignedBigInteger('eia_tamano_bytes')->nullable();
            $table->timestamp('eia_created_at')->nullable();
            $table->timestamp('eia_updated_at')->nullable();
            $table->foreign('eia_eim_id')->references('eim_id')->on('tbl_etiqueta_impresiones_eim');
            $table->foreign('eia_etf_id')->references('etf_id')->on('tbl_etiqueta_formatos_etf');
        });

        Schema::create('tbl_etiqueta_impresion_detalles_eid', function (Blueprint $table) {
            $table->bigIncrements('eid_id');
            $table->unsignedBigInteger('eid_eim_id');
            $table->unsignedBigInteger('eid_rmd_id')->nullable();
            $table->unsignedBigInteger('eid_psk_id')->nullable();
            $table->unsignedBigInteger('eid_etf_id')->nullable();
            $table->unsignedBigInteger('eid_etp_id')->nullable();
            $table->decimal('eid_cantidad_recibida', 14, 2);
            $table->unsignedInteger('eid_etiquetas')->default(0);
            $table->json('eid_snapshot')->nullable();
            $table->timestamp('eid_created_at')->nullable();
            $table->timestamp('eid_updated_at')->nullable();
            $table->foreign('eid_eim_id')->references('eim_id')->on('tbl_etiqueta_impresiones_eim');
        });

        $now = now();
        foreach ([
            ['etiquetas.formatos.ver', 'Consultar formatos de etiqueta'], ['etiquetas.formatos.crear', 'Crear formatos de etiqueta'],
            ['etiquetas.formatos.editar', 'Editar formatos de etiqueta'], ['etiquetas.lineas.asignar', 'Asignar formatos a líneas'],
            ['etiquetas.plantillas.gestionar', 'Gestionar plantillas de etiqueta'], ['etiquetas.reglas_unidad.gestionar', 'Gestionar reglas de unidades para etiquetas'],
            ['etiquetas.generar', 'Generar etiquetas desde recepciones'], ['etiquetas.reimprimir', 'Reimprimir etiquetas'],
            ['etiquetas.historial.ver', 'Consultar historial de etiquetas'], ['etiquetas.archivos.descargar', 'Descargar archivos de etiquetas'],
        ] as [$clave, $descripcion]) {
            DB::table('tbl_permisos_prm')->updateOrInsert(['prm_clave' => $clave], [
                'prm_descripcion' => $descripcion, 'prm_modulo' => 'etiquetas', 'prm_estatus' => 'activo',
                'prm_deleted' => false, 'prm_deleted_at' => null, 'prm_updated_at' => $now,
            ]);
        }

        $administradores = DB::table('tbl_roles_rol')->whereIn('rol_nombre', ['Administrador', 'Administrador del Sistema'])->pluck('rol_id');
        $permisosEtiquetas = DB::table('tbl_permisos_prm')->where('prm_modulo', 'etiquetas')->pluck('prm_id');
        foreach ($administradores as $rolId) foreach ($permisosEtiquetas as $permisoId) {
            DB::table('tbl_rol_permisos_rpm')->updateOrInsert(
                ['rpm_rol_id' => $rolId, 'rpm_prm_id' => $permisoId, 'rpm_deleted' => false],
                ['rpm_estatus' => 'activo', 'rpm_deleted_at' => null]
            );
        }

        foreach (['UMD_PZA' => 'por_unidad_recibida', 'UMD_M' => 'por_detalle_recepcion'] as $clave => $regla) {
            $unidadId = DB::table('tbl_unidades_medida_umd')->where('umd_clave', $clave)->where('umd_deleted', false)->value('umd_id');
            if ($unidadId) DB::table('tbl_etiqueta_unidad_reglas_eur')->updateOrInsert(
                ['eur_umd_id' => $unidadId, 'eur_deleted' => false],
                ['eur_regla' => $regla, 'eur_estatus' => 'activo', 'eur_created_at' => $now, 'eur_updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_etiqueta_impresion_detalles_eid');
        Schema::dropIfExists('tbl_etiqueta_impresion_archivos_eia');
        Schema::dropIfExists('tbl_etiqueta_impresiones_eim');
        Schema::dropIfExists('tbl_etiqueta_unidad_reglas_eur');
        Schema::dropIfExists('tbl_etiqueta_linea_config_elc');
        Schema::dropIfExists('tbl_etiqueta_plantillas_etp');
        Schema::dropIfExists('tbl_etiqueta_formatos_etf');
        DB::table('tbl_permisos_prm')->where('prm_modulo', 'etiquetas')->delete();
    }
};
