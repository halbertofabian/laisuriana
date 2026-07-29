<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_dispositivo_impresoras_dip', function (Blueprint $table) {
            $table->bigIncrements('dip_id');
            $table->string('dip_device_uid', 64)->unique();
            $table->string('dip_nombre_dispositivo', 120);
            $table->string('dip_tipo_conexion', 20);
            $table->string('dip_nombre_impresora', 160);
            $table->string('dip_host', 190)->nullable();
            $table->unsignedInteger('dip_puerto')->nullable();
            $table->string('dip_controlador', 80)->nullable();
            $table->string('dip_agent_url', 255)->nullable();
            $table->unsignedBigInteger('dip_created_by_usr_id')->nullable()->index();
            $table->unsignedBigInteger('dip_updated_by_usr_id')->nullable()->index();
            $table->timestamp('dip_created_at')->nullable();
            $table->timestamp('dip_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_dispositivo_impresoras_dip');
    }
};
