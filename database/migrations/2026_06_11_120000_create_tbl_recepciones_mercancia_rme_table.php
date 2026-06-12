<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_recepciones_mercancia_rme', function (Blueprint $table) {
            $table->bigIncrements('rme_id');
            $table->string('rme_folio', 50)->unique();
            $table->unsignedBigInteger('rme_scl_id')->nullable()->index();
            $table->unsignedBigInteger('rme_alm_id')->nullable()->index();
            $table->unsignedBigInteger('rme_prv_id')->nullable()->index();
            $table->unsignedBigInteger('rme_dominante_atr_id')->nullable()->index();
            $table->string('rme_documento_tipo', 40)->nullable()->index();
            $table->string('rme_documento_referencia', 120)->nullable()->index();
            $table->string('rme_descuento_tipo', 20)->nullable();
            $table->decimal('rme_descuento_valor', 14, 2)->nullable();
            $table->decimal('rme_flete_total', 14, 2)->nullable();
            $table->decimal('rme_iva_porcentaje', 6, 2)->nullable();
            $table->timestamp('rme_fecha_captura')->nullable()->index();
            $table->timestamp('rme_fecha_emision')->nullable()->index();
            $table->text('rme_motivo_texto')->nullable();
            $table->text('rme_observaciones')->nullable();
            $table->json('rme_payload')->nullable();
            $table->string('rme_estado', 20)->default('borrador')->index();
            $table->timestamp('rme_confirmado_at')->nullable()->index();
            $table->unsignedBigInteger('rme_confirmado_by_usr_id')->nullable()->index();
            $table->timestamp('rme_cancelado_at')->nullable()->index();
            $table->unsignedBigInteger('rme_cancelado_by_usr_id')->nullable()->index();
            $table->text('rme_cancelacion_motivo')->nullable();
            $table->unsignedBigInteger('rme_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('rme_updated_by_usr_id')->nullable()->index();
            $table->boolean('rme_deleted')->default(false)->index();
            $table->timestamp('rme_deleted_at')->nullable()->index();
            $table->timestamp('rme_created_at')->nullable();
            $table->timestamp('rme_updated_at')->nullable();

            $table->foreign('rme_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->foreign('rme_alm_id')->references('alm_id')->on('tbl_almacenes_alm');
            $table->foreign('rme_prv_id')->references('prv_id')->on('tbl_proveedores_prv');
            $table->foreign('rme_dominante_atr_id')->references('atr_id')->on('tbl_atributos_atr');
            $table->foreign('rme_confirmado_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('rme_cancelado_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('rme_created_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('rme_updated_by_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->index(['rme_estado', 'rme_created_at'], 'idx_rme_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_recepciones_mercancia_rme');
    }
};
