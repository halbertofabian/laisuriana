<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_bitacora_accesos_bac', function (Blueprint $table) {
            $table->bigIncrements('bac_id');
            $table->unsignedBigInteger('bac_usr_id')->nullable()->index();
            $table->string('bac_usuario_intentado', 60)->nullable()->index();
            $table->string('bac_resultado', 20)->index();
            $table->string('bac_motivo', 220)->nullable();
            $table->string('bac_ip', 45)->nullable()->index();
            $table->text('bac_user_agent')->nullable();
            $table->boolean('bac_deleted')->default(false)->index();
            $table->timestamp('bac_deleted_at')->nullable()->index();
            $table->timestamp('bac_created_at')->nullable();
            $table->timestamp('bac_updated_at')->nullable();

            $table->foreign('bac_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->index(['bac_resultado', 'bac_created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_bitacora_accesos_bac');
    }
};
