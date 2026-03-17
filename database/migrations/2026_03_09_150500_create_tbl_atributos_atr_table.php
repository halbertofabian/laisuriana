<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_atributos_atr', function (Blueprint $table) {
            $table->bigIncrements('atr_id');
            $table->string('atr_nombre', 120);
            $table->string('atr_clave', 40);
            $table->string('atr_tipo', 40)->nullable();
            $table->string('atr_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('atr_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('atr_updated_by_usr_id')->nullable()->index();
            $table->boolean('atr_deleted')->default(false)->index();
            $table->timestamp('atr_deleted_at')->nullable()->index();
            $table->timestamp('atr_created_at')->nullable();
            $table->timestamp('atr_updated_at')->nullable();

            $table->unique(['atr_clave', 'atr_deleted'], 'uk_atributo_clave_activo');
            $table->unique(['atr_nombre', 'atr_deleted'], 'uk_atributo_nombre_activo');
            $table->index(['atr_deleted', 'atr_estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_atributos_atr');
    }
};
