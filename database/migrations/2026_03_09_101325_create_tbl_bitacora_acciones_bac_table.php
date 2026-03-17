<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_bitacora_acciones_bac', function (Blueprint $table) {
            $table->bigIncrements('bac_id');
            $table->unsignedBigInteger('bac_usr_id')->nullable()->index();
            $table->unsignedBigInteger('bac_scl_id')->nullable()->index();
            $table->string('bac_accion', 120)->index();
            $table->string('bac_entidad', 120)->nullable()->index();
            $table->string('bac_entidad_id', 80)->nullable()->index();
            $table->json('bac_payload')->nullable();
            $table->string('bac_ip', 45)->nullable()->index();
            $table->text('bac_user_agent')->nullable();
            $table->boolean('bac_deleted')->default(false)->index();
            $table->timestamp('bac_deleted_at')->nullable()->index();
            $table->timestamp('bac_created_at')->nullable();
            $table->timestamp('bac_updated_at')->nullable();

            $table->foreign('bac_usr_id')->references('usr_id')->on('tbl_usuarios_usr');
            $table->foreign('bac_scl_id')->references('scl_id')->on('tbl_sucursales_scl');
            $table->index(['bac_accion', 'bac_created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_bitacora_acciones_bac');
    }
};
