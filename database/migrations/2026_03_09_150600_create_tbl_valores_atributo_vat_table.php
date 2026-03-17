<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_valores_atributo_vat', function (Blueprint $table) {
            $table->bigIncrements('vat_id');
            $table->unsignedBigInteger('vat_atr_id');
            $table->string('vat_valor', 120);
            $table->string('vat_clave', 40);
            $table->string('vat_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('vat_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('vat_updated_by_usr_id')->nullable()->index();
            $table->boolean('vat_deleted')->default(false)->index();
            $table->timestamp('vat_deleted_at')->nullable()->index();
            $table->timestamp('vat_created_at')->nullable();
            $table->timestamp('vat_updated_at')->nullable();

            $table->foreign('vat_atr_id')->references('atr_id')->on('tbl_atributos_atr');
            $table->unique(['vat_atr_id', 'vat_clave', 'vat_deleted'], 'uk_valor_atributo_clave_activo');
            $table->unique(['vat_atr_id', 'vat_valor', 'vat_deleted'], 'uk_valor_atributo_valor_activo');
            $table->index(['vat_atr_id', 'vat_deleted', 'vat_estatus'], 'idx_vat_atr_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_valores_atributo_vat');
    }
};
