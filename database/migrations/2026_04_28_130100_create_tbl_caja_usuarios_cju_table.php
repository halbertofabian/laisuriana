<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_caja_usuarios_cju', function (Blueprint $table) {
            $table->bigIncrements('cju_id');
            $table->unsignedBigInteger('cju_caj_id');
            $table->unsignedBigInteger('cju_usr_id');
            $table->string('cju_estatus', 20)->default('activo')->index();
            $table->boolean('cju_deleted')->default(false)->index();
            $table->timestamp('cju_deleted_at')->nullable()->index();
            $table->timestamp('cju_created_at')->nullable();
            $table->timestamp('cju_updated_at')->nullable();

            $table->foreign('cju_caj_id')->references('caj_id')->on('tbl_cajas_caj');
            $table->foreign('cju_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->unique(['cju_caj_id', 'cju_usr_id', 'cju_deleted'], 'uk_caja_usuario_activo');
            $table->index(['cju_usr_id', 'cju_deleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_caja_usuarios_cju');
    }
};
