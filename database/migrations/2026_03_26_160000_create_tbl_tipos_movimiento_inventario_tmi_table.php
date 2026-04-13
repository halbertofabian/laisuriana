<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_tipos_movimiento_inventario_tmi', function (Blueprint $table) {
            $table->bigIncrements('tmi_id');
            $table->string('tmi_clave', 80)->unique();
            $table->string('tmi_nombre', 120);
            $table->string('tmi_naturaleza', 20)->default('entrada')->index();
            $table->string('tmi_clase', 20)->default('entrada')->index();
            $table->string('tmi_estatus', 20)->default('activo')->index();
            $table->unsignedBigInteger('tmi_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('tmi_updated_by_usr_id')->nullable()->index();
            $table->boolean('tmi_deleted')->default(false)->index();
            $table->timestamp('tmi_deleted_at')->nullable()->index();
            $table->timestamp('tmi_created_at')->nullable();
            $table->timestamp('tmi_updated_at')->nullable();

            $table->index(['tmi_clase', 'tmi_deleted', 'tmi_estatus'], 'idx_tmi_clase_del_est');
            $table->index(['tmi_naturaleza', 'tmi_deleted', 'tmi_estatus'], 'idx_tmi_nat_del_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tipos_movimiento_inventario_tmi');
    }
};
